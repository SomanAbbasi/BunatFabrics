<?php

declare(strict_types=1);

// Creates a separate disposable database on the explicitly supplied test server.
// Never drops a database; never runs migrations against the configured database.
require __DIR__ . '/../bootstrap.php';

use App\Core\Migrator;
use App\Admin\Orders\OrderService as AdminOrders;
use App\Orders\OrderService as CustomerOrders;

$dsn = getenv('CATALOG_TEST_DSN') ?: '';
if ($dsn === '' || str_contains(strtolower($dsn), 'dbname=')) {
    exit("Set CATALOG_TEST_DSN to an isolated MySQL server without a dbname.\n");
}
$user = getenv('CATALOG_TEST_USER') ?: 'root';
$password = getenv('CATALOG_TEST_PASSWORD') ?: '';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
$server = new PDO($dsn, $user, $password, $options);
$name = 'bunat_orders_legacy_test_' . bin2hex(random_bytes(6));
$server->exec("CREATE DATABASE `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = new PDO($dsn . ';dbname=' . $name, $user, $password, $options);
$schema = file_get_contents(base_path('database/schema.sql'));
$pdo->exec(substr($schema, strpos($schema, 'CREATE TABLE')));
$pdo->exec('CREATE TABLE schema_migrations (version VARCHAR(190) NOT NULL PRIMARY KEY, checksum CHAR(64) NOT NULL, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
$mark = $pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (?, ?)');
foreach (['001_categories.sql', '002_products.sql', '003_product_price.sql', '004_product_images.sql', '005_orders.sql', '006_order_contact.sql'] as $file) {
    $path = base_path('database/migrations/' . $file);
    $pdo->exec(file_get_contents($path));
    $mark->execute([$file, hash_file('sha256', $path)]);
}
function verify(bool $condition, string $message): void
{
    if (! $condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}
$insert = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
$insert->execute(['Legacy Customer','legacy@example.test',password_hash('TestPassword123', PASSWORD_BCRYPT),'user']);
$customerId = (int) $pdo->lastInsertId();
$insert->execute(['Legacy Admin','admin@example.test',password_hash('TestPassword123', PASSWORD_BCRYPT),'admin']);
$adminId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO categories (name,slug,description,is_active) VALUES ('Legacy','legacy','',1)");
$categoryId = (int) $pdo->lastInsertId();
$insert = $pdo->prepare("INSERT INTO products (category_id,product_code,title,description,unit,price_pkr,status) VALUES (?, 'OLD-1', 'Legacy fabric', 'Fabric', 'yard', 19.99, 'published')");
$insert->execute([$categoryId]);
$productId = (int) $pdo->lastInsertId();
$insert = $pdo->prepare("INSERT INTO orders (user_id,status,subtotal_pkr,contact_phone,shipping_address) VALUES (?, 'pending', 59.97, '+923001234567', '12 Test Street')");
$insert->execute([$customerId]);
$orderId = (int) $pdo->lastInsertId();
$insert = $pdo->prepare("INSERT INTO order_items (order_id,product_id,product_code,title,unit,unit_price_pkr,quantity,line_total_pkr) VALUES (?,?,'OLD-1','Legacy fabric','yard',19.99,3,59.97)");
$insert->execute([$orderId,$productId]);
Migrator::run($pdo, base_path('database/migrations'));
verify(Migrator::run($pdo, base_path('database/migrations')) === [], 'legacy migrations repeat safely');
$admin = new AdminOrders($pdo);
$listing = $admin->listing(['q'=>'','status'=>'','date'=>''],1);
$old = $listing['orders'][0];
verify((int) $old['id'] === $orderId && $old['subtotal'] === '59.97' && $old['customer'] === 'Legacy Customer', 'legacy order retained for admin');
verify($old['items'][0]['fabric_title'] === 'Legacy fabric' && $old['items'][0]['unit_price'] === '19.99' && $old['items'][0]['line_total'] === '59.97', 'legacy snapshots retained');
verify($admin->transition($orderId,'pending','confirmed',['id'=>$adminId,'name'=>'Legacy Admin']) === 'ok', 'legacy order status can advance');
$buyer = new CustomerOrders($pdo);
$newId = $buyer->place($customerId, [['id'=>$productId,'quantity'=>3,'price_pkr'=>'19.99']], ['phone'=>'+923001234567','address'=>'12 Test Street','city'=>'Lahore','payment_method'=>'cod']);
$statement = $pdo->prepare('SELECT subtotal, subtotal_pkr FROM orders WHERE id=?');
$statement->execute([$newId]); $new = $statement->fetch();
verify($new['subtotal'] === '59.97' && $new['subtotal_pkr'] === '59.97', 'new order supports both legacy and admin totals');
$statement = $pdo->prepare('SELECT fabric_title,title,unit_price,unit_price_pkr,line_total,line_total_pkr FROM order_items WHERE order_id=?');
$statement->execute([$newId]); $new = $statement->fetch();
verify($new['fabric_title'] === $new['title'] && $new['unit_price'] === $new['unit_price_pkr'] && $new['line_total'] === $new['line_total_pkr'], 'new snapshots support legacy and admin columns');
$before = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
try {
    $buyer->place($customerId, [['id'=>$productId,'quantity'=>1,'price_pkr'=>'18.00']], ['phone'=>'+923001234567','address'=>'12 Test Street','city'=>'Lahore','payment_method'=>'cod']);
    throw new RuntimeException('Changed price was accepted');
} catch (RuntimeException $exception) {
    verify($exception->getMessage() === 'The catalog changed. Please review the cart again.', 'changed price rejected');
}
verify((int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn() === $before, 'failed order leaves no partial records');
echo "Disposable database retained: {$name}\n";
