<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Application bootstrap
|--------------------------------------------------------------------------
| Loaded by public/index.php, the CLI migration script and any other entry
| point. It wires up autoloading, the .env file, helpers and the session.
*/

define('BASE_PATH', __DIR__);

/*
 * PSR-4 style autoloader: App\Core\Env -> src/Core/Env.php
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = BASE_PATH . '/src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/src/Core/Env.php';
require BASE_PATH . '/src/Core/helpers.php';

App\Core\Env::load(BASE_PATH . '/.env');

App\Core\Session::start();

/*
 * Report every error while debugging, never leak them in production.
 */
$debug = (bool) config('app.debug');

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

date_default_timezone_set('UTC');
