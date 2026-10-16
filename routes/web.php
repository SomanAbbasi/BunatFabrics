<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Required from public/index.php. The $router instance is created there.
*/

/** @var App\Core\Router $router */

use App\Auth\AuthController;
use App\Auth\DashboardController;

$router->get('/', [AuthController::class, 'showLogin']);

$router->get('/signup', [AuthController::class, 'showSignup']);
$router->post('/signup', [AuthController::class, 'signup']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);
