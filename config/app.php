<?php

declare(strict_types=1);

use App\Core\Env;

/*
|--------------------------------------------------------------------------
| Application configuration
|--------------------------------------------------------------------------
| Values are read from the .env file through the Env helper. Every config
| file owns a single concern so it stays easy to find and change a setting.
*/

return [
    'name'  => Env::get('APP_NAME', 'Bunat Fabrics'),
    'env'   => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url'   => rtrim(Env::get('APP_URL', 'http://localhost'), '/'),
];
