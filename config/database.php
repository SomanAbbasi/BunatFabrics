<?php

declare(strict_types=1);

use App\Core\Env;

/*
|--------------------------------------------------------------------------
| Database configuration
|--------------------------------------------------------------------------
| Only connection details live here. The PDO connection itself is created
| by the Core\Database class so config stays declarative.
*/

return [
    'driver'   => Env::get('DB_CONNECTION', 'mysql'),
    'host'     => Env::get('DB_HOST', '127.0.0.1'),
    'port'     => Env::get('DB_PORT', '3306'),
    'database' => Env::get('DB_NAME', 'bunat_fabrics'),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
    'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
];
