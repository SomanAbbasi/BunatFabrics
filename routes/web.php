<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
| Required from public/index.php. The $router instance is created there.
*/

/** @var App\Core\Router $router */

use App\Admin\Categories\CategoryController;
use App\Admin\Dashboard\DashboardController as AdminDashboardController;
use App\Admin\Orders\AdminOrderController;
use App\Admin\Orders\OrderApiController;
use App\Admin\Products\ProductController;
use App\Admin\Settings\SettingsController;
use App\Auth\AuthController;
use App\Auth\AccountSettingsController;
use App\Auth\DashboardController;
use App\Cart\CartController;
use App\Home\HomeController;
use App\Home\ProductController as HomeProductController;
use App\Orders\CustomerOrderController;

// Storefront & Products
$router->get('/', [HomeController::class, 'index']);
$router->get('/product', [HomeProductController::class, 'show']);

// Wholesale Cart & Checkout
$router->get('/cart', [CartController::class, 'index']);
$router->get('/cart/data', [CartController::class, 'data']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->get('/checkout', [CartController::class, 'checkout']);
$router->post('/checkout', [CartController::class, 'placeOrder']);
$router->get('/checkout/success', [CartController::class, 'success']);

// Authentication
$router->get('/forgot-password', [App\Auth\RecoveryController::class, 'forgot']);
$router->post('/forgot-password', [App\Auth\RecoveryController::class, 'request']);
$router->get('/reset-password', [App\Auth\RecoveryController::class, 'form']);
$router->post('/reset-password', [App\Auth\RecoveryController::class, 'reset']);
$router->get('/signup', [AuthController::class, 'showSignup']);
$router->post('/signup', [AuthController::class, 'signup']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Customer Portal
$router->get('/settings', [AccountSettingsController::class, 'index']);
$router->post('/settings/profile', [AccountSettingsController::class, 'updateProfile']);
$router->post('/settings/password', [AccountSettingsController::class, 'updatePassword']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/orders', [CustomerOrderController::class, 'index']);
$router->get('/orders/show', [CustomerOrderController::class, 'show']);

// Admin Control Center
$router->get('/admin', [AdminDashboardController::class, 'index']);
$router->get('/admin/orders', [AdminOrderController::class, 'index']);
$router->get('/admin/orders/show', [AdminOrderController::class, 'show']);
$router->post('/admin/orders/status', [AdminOrderController::class, 'updateStatus']);
$router->get('/admin/settings', [SettingsController::class, 'index']);
$router->post('/admin/settings/profile', [SettingsController::class, 'updateProfile']);
$router->post('/admin/settings/password', [SettingsController::class, 'updatePassword']);

// Admin API
$router->get('/api/admin/orders', [OrderApiController::class, 'index']);
$router->get('/api/admin/orders/show', [OrderApiController::class, 'show']);
$router->post('/api/admin/orders/status', [OrderApiController::class, 'updateStatus']);

// Admin Catalog Management
$router->get('/admin/categories', [CategoryController::class, 'index']);
$router->get('/admin/categories/create', [CategoryController::class, 'create']);
$router->get('/admin/categories/edit', [CategoryController::class, 'edit']);
$router->post('/admin/categories/store', [CategoryController::class, 'store']);
$router->post('/admin/categories/update', [CategoryController::class, 'update']);
$router->post('/admin/categories/state', [CategoryController::class, 'state']);

$router->get('/admin/products', [ProductController::class, 'index']);
$router->get('/admin/products/create', [ProductController::class, 'create']);
$router->get('/admin/products/edit', [ProductController::class, 'edit']);
$router->post('/admin/products/store', [ProductController::class, 'store']);
$router->post('/admin/products/update', [ProductController::class, 'update']);
$router->post('/admin/products/state', [ProductController::class, 'state']);
$router->post('/admin/products/images/delete', [ProductController::class, 'deleteImage']);
