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

// Catalog routes use literal paths; edit IDs are validated query/form values.
$router->get('/admin/categories', [\App\Admin\Categories\CategoryController::class, 'index']);
$router->get('/admin/categories/create', [\App\Admin\Categories\CategoryController::class, 'create']);
$router->get('/admin/categories/edit', [\App\Admin\Categories\CategoryController::class, 'edit']);
$router->post('/admin/categories/store', [\App\Admin\Categories\CategoryController::class, 'store']);
$router->post('/admin/categories/update', [\App\Admin\Categories\CategoryController::class, 'update']);
$router->post('/admin/categories/state', [\App\Admin\Categories\CategoryController::class, 'state']);
$router->get('/admin/products', [\App\Admin\Products\ProductController::class, 'index']);
$router->get('/admin/products/create', [\App\Admin\Products\ProductController::class, 'create']);
$router->get('/admin/products/edit', [\App\Admin\Products\ProductController::class, 'edit']);
$router->post('/admin/products/store', [\App\Admin\Products\ProductController::class, 'store']);
$router->post('/admin/products/update', [\App\Admin\Products\ProductController::class, 'update']);
$router->post('/admin/products/state', [\App\Admin\Products\ProductController::class, 'state']);
