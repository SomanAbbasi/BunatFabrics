<?php

declare(strict_types=1);

// Test-only front controller: never falls back to the application's database.
$dsn = getenv('SETTINGS_TEST_DSN') ?: '';
$database = getenv('SETTINGS_TEST_DATABASE') ?: '';
if (PHP_SAPI !== 'cli-server' || $dsn === '' || str_contains(strtolower($dsn), 'dbname=') || !preg_match('/^bunat_settings_test_[a-f0-9]{12}$/', $database)) {
    http_response_code(503);
    exit('Explicit disposable settings database required.');
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/assets/')) {
    $file = realpath(__DIR__ . '/../public' . $path);
    if ($file && str_starts_with($file, realpath(__DIR__ . '/../public/assets') . DIRECTORY_SEPARATOR) && is_file($file)) {
        return false;
    }
}
require __DIR__ . '/../bootstrap.php';

$pdo = new PDO($dsn, getenv('SETTINGS_TEST_USER') ?: 'root', getenv('SETTINGS_TEST_PASSWORD') ?: '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec("USE `$database`");
(new ReflectionProperty(App\Core\Database::class, 'connection'))->setValue(null, $pdo);
$environment = new ReflectionProperty(App\Core\Env::class, 'items');
$environment->setValue(null, array_replace($environment->getValue(), ['APP_URL' => 'http://127.0.0.1:8098', 'APP_ENV' => 'testing', 'APP_DEBUG' => 'false']));

if ($path === '/__settings_test') {
    exit('isolated-settings-test');
}
$router = new App\Core\Router();
require base_path('routes/web.php');
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
