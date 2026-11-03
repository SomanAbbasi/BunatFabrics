<?php

declare(strict_types=1);

// Run: php -d session.save_path=/tmp tests/catalog.php
// Creates a uniquely named disposable database. Never resets/drops a database.
require __DIR__ . '/../bootstrap.php';

use App\Admin\Categories\CategoryService;
use App\Admin\Products\ProductService;
use App\Core\Database;
use App\Core\Migrator;
use App\Home\CatalogService;

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

if (! in_array(config('app.env'), ['local', 'development', 'testing'], true)) {
    exit("Tests require a development/testing environment.\n");
}
$source = Database::connection();
$originalUsers = $source->query('SELECT * FROM users ORDER BY id')->fetchAll();
$name = 'bunat_catalog_test_' . bin2hex(random_bytes(6));
$testDsn = getenv('CATALOG_TEST_DSN');
$server = $testDsn ? new PDO($testDsn, getenv('CATALOG_TEST_USER') ?: 'root', getenv('CATALOG_TEST_PASSWORD') ?: '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]) : $source;
try {
    $server->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $exception) {
    fwrite(STDERR, "Cannot create disposable database. Supply CATALOG_TEST_DSN and test-only credentials for a separate MySQL server. No application data changed.\n");
    exit(1);
}
$config = config('database');
$dsn = $testDsn ? $testDsn . ';dbname=' . $name : "mysql:host={$config['host']};port={$config['port']};dbname={$name};charset=utf8mb4";
$pdo = new PDO($dsn, $testDsn ? (getenv('CATALOG_TEST_USER') ?: 'root') : $config['username'], $testDsn ? (getenv('CATALOG_TEST_PASSWORD') ?: '') : $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
$schema = file_get_contents(base_path('database/schema.sql'));
$pdo->exec(substr($schema, strpos($schema, 'CREATE TABLE')));
$hash = password_hash('TestAdmin123', PASSWORD_BCRYPT);
$statement = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
$statement->execute(['Test Administrator', 'admin@example.test', $hash, 'admin']);
$statement->execute(['Test Customer', 'customer@example.test', password_hash('TestCustomer123', PASSWORD_BCRYPT), 'user']);
$before = $pdo->query('SELECT * FROM users ORDER BY id')->fetchAll();
$migrationCount = count((array) glob(base_path('database/migrations') . '/*.sql'));
check(count(Migrator::run($pdo, base_path('database/migrations'))) === $migrationCount, 'all catalog migrations applied');
check(Migrator::run($pdo, base_path('database/migrations')) === [], 'second migration run is a no-op');
check($pdo->query('SELECT * FROM users ORDER BY id')->fetchAll() === $before, 'migration preserves all account fields and password hashes');
$pdo->beginTransaction();
try {
    $categories = new CategoryService($pdo);
    $products = new ProductService($pdo);
    $catalog = new CatalogService($pdo);
    check($categories->listing(1)['items'] === [], 'empty categories');
    check($products->listing(1)['items'] === [], 'empty products');
    $category = ['name' => 'Cotton', 'slug' => 'cotton', 'description' => 'Cotton fabrics', 'is_active' => '1'];
    $saved = $categories->save($category);
    check($saved['errors'] === [], 'create category');
    $categoryId = $saved['id'];
    check(isset($categories->save($category)['errors']['name']), 'unique category name');
    check(isset($categories->save($category + [])['errors']['slug']), 'unique category slug');
    check($categories->save(array_replace($category, ['name' => 'Cotton blends']), $categoryId)['errors'] === [], 'edit category');
    check(count($categories->listing(1)['items']) === 1, 'list categories');
    $data = ['product_code' => 'FAB-001', 'title' => 'Cotton lawn', 'description' => '<script>test</script>', 'category_id' => (string) $categoryId, 'composition' => '100% cotton', 'width_inch' => '58.5', 'weight_gsm' => '120', 'unit' => 'meter', 'status' => 'draft'];
    $saved = $products->save($data);
    check($saved['errors'] === [], 'create draft product');
    $id = $saved['id'];
    check($products->find($id)['width_inch'] === '58.5', 'exact decimal width retained');
    check($catalog->published(1)['products'] === [], 'draft excluded publicly');
    check(isset($products->save($data)['errors']['product_code']), 'duplicate code rejected');
    check(isset($products->save(array_replace($data, ['product_code' => 'BAD-CAT', 'category_id' => '999999']))['errors']['category_id']), 'missing category rejected');
    check(isset($products->save(array_replace($data, ['product_code' => 'BAD-WIDTH', 'width_inch' => '1.25']))['errors']['width_inch']), 'excess precision rejected');
    check(isset($products->save(array_replace($data, ['product_code' => [], 'category_id' => []]))['errors']['category_id']), 'malformed input rejected');
    check($products->save(array_replace($data, ['title' => 'Updated cotton']), $id)['errors'] === [], 'edit product');
    check($products->setStatus($id, 'published'), 'publish product');
    check(count($catalog->published(1)['products']) === 1, 'published product visible');
    check($categories->setVisibility($categoryId, '0'), 'hide category');
    check($catalog->published(1)['products'] === [], 'hidden category excludes published products');
    check($categories->setVisibility($categoryId, '1'), 'enable category');
    check(count($catalog->published(1)['products']) === 1, 'enabled category restores published products');
    check($products->setStatus($id, 'archived'), 'archive product');
    check($catalog->published(1)['products'] === [], 'archived product excluded');
    check(! $products->setStatus($id, 'invalid'), 'invalid state rejected');
    check($products->setStatus($id, 'draft'), 'unpublish product');
    $restricted = false;
    try {
        $statement = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $statement->execute([$categoryId]);
    } catch (PDOException $exception) {
        $restricted = ($exception->errorInfo[1] ?? null) === 1451;
    }
    check($restricted, 'foreign key prevents removal of category in use');
    for ($i = 0; $i < 21; $i++) {
        $result = $products->save(array_replace($data, ['product_code' => 'PAGE-' . $i, 'status' => 'published']));
        check($result['errors'] === [], 'pagination fixture ' . $i);
    }
    check(count($products->listing(1)['items']) === 20 && $products->listing(1)['hasNext'], 'admin pagination');
    check(count($catalog->published(1)['products']) === 12 && $catalog->published(1)['hasNext'], 'public pagination');
} finally {
    $pdo->rollBack();
}
check($source->query('SELECT * FROM users ORDER BY id')->fetchAll() === $originalUsers, 'configured database users unchanged');
echo 'TEST_DATABASE=', $name, PHP_EOL;
echo "Disposable database retained for browser tests/manual cleanup; no database was dropped.\n";
