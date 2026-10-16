<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Front controller
|--------------------------------------------------------------------------
| Every request enters here. Apache rewrites unknown paths to this file
| (see public/.htaccess), which then delegates to the router.
*/

require __DIR__ . '/../bootstrap.php';

use App\Core\Router;

$router = new Router();

require base_path('routes/web.php');

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
);
