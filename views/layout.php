<?php

declare(strict_types=1);

use App\Auth\Guard;
use App\Cart\CartService;
use App\Core\Session;

$currentUser = Guard::user();
$cartCount = (new CartService())->count();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(Session::csrfToken()) ?>">
    <meta name="app-url" content="<?= e(url('/')) ?>">
    <title><?= e($title) ?> &middot; <?= e(config('app.name')) ?></title>
    <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
    <script src="<?= e(url('/assets/js/app.js')) ?>" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand-mark" aria-hidden="true">B</span>
            <span class="brand-name">Bunat <em>Fabrics</em></span>
        </a>

        <nav class="site-nav" aria-label="Site navigation">
            <a class="nav-link" href="<?= e(url('/')) ?>"><?= icon('grid') ?><span>Fabrics</span></a>
            <a class="nav-link nav-cart<?= $cartCount > 0 ? ' has-items' : '' ?>" href="<?= e(url('/cart')) ?>">
                <?= icon('cart') ?>
                <span>Cart</span>
                <span class="cart-count" id="header-cart-count" <?= $cartCount > 0 ? '' : 'style="display:none"' ?>><?= e((string) $cartCount) ?></span>
            </a>
            <?php if ($currentUser !== null): ?>
                <?php $profileUser = $currentUser; require base_path('views/partials/profile-menu.php'); ?>
            <?php else: ?>
                <a class="nav-link" href="<?= e(url('/login')) ?>"><?= icon('user') ?><span>Sign in</span></a>
                <a class="nav-cta" href="<?= e(url('/signup')) ?>"><?= icon('user-plus') ?><span>Create account</span></a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <?php if ($status = Session::pullFlash('status')): ?>
            <div class="alert alert-success" role="status"><?= icon('check-circle', 'icon alert-icon') ?><span><?= e($status) ?></span></div>
        <?php endif; ?>

        <?php if ($error = Session::pullFlash('error')): ?>
            <div class="alert alert-error" role="alert"><?= icon('alert-circle', 'icon alert-icon') ?><span><?= e($error) ?></span></div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="site-footer">
        &copy; <?= date('Y') ?> <?= e(config('app.name')) ?> &middot; Wholesale fabric ordering
    </footer>
</body>
</html>
