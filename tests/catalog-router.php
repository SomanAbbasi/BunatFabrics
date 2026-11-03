<?php

declare(strict_types=1);

// Test-only PHP server router. Never place this file in public/.
$testDatabase = getenv('CATALOG_TEST_DATABASE') ?: '';
$testDsn = getenv('CATALOG_TEST_DSN') ?: '';
if (PHP_SAPI !== 'cli-server' || ! preg_match('/^bunat_catalog_test_[a-f0-9]{12}$/D', $testDatabase) || $testDsn === '' || str_contains($testDsn, 'dbname=')) {
    http_response_code(503);
    exit('A separate disposable catalog database and server DSN are required.');
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/assets/')) {
    return false;
}

// Override URL/debug only in this test process. The real .env is never edited.
function config(string $key, mixed $default = null): mixed
{
    [$file, $item] = array_pad(explode('.', $key, 2), 2, null);
    $data = require dirname(__DIR__) . '/config/' . $file . '.php';
    if ($file === 'app') {
        $data['url'] = getenv('CATALOG_TEST_URL') ?: 'http://127.0.0.1:8097';
        $data['debug'] = false;
    }
    return $item === null ? $data : ($data[$item] ?? $default);
}

require dirname(__DIR__) . '/bootstrap.php';
$pdo = new PDO($testDsn . ';dbname=' . $testDatabase, getenv('CATALOG_TEST_USER') ?: 'root', getenv('CATALOG_TEST_PASSWORD') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $pdo);
if ($path === '/__catalog_test') {
    echo 'isolated-catalog-test';
    exit;
}
$router = new App\Core\Router();
require base_path('routes/web.php');
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
