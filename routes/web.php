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
use App\Admin\Dashboard\DashboardController as AdminDashboardController;
use App\Auth\DashboardController;
use App\Home\HomeController;

$router->get('/', [HomeController::class, 'index']);

$router->get('/signup', [AuthController::class, 'showSignup']);
$router->post('/signup', [AuthController::class, 'signup']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/admin', [AdminDashboardController::class, 'index']);
