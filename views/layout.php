<?php

declare(strict_types=1);

use App\Auth\Guard;
use App\Core\Session;

$currentUser = Guard::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <a class="nav-link" href="<?= e(url('/')) ?>">Fabrics</a>
            <?php if ($currentUser !== null): ?>
                <span class="nav-user">Hi, <?= e($currentUser['name']) ?></span>
                <a class="nav-link" href="<?= e(url('/dashboard')) ?>">Dashboard</a>
                <form action="<?= e(url('/logout')) ?>" method="POST" class="inline-form">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <button type="submit" class="link-button">Sign out</button>
                </form>
            <?php else: ?>
                <a class="nav-link" href="<?= e(url('/login')) ?>">Sign in</a>
                <a class="nav-cta" href="<?= e(url('/signup')) ?>">Create account</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="container">
        <?php if ($status = Session::pullFlash('status')): ?>
            <div class="alert alert-success"><?= e($status) ?></div>
        <?php endif; ?>

        <?php if ($error = Session::pullFlash('error')): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="site-footer">
        &copy; <?= date('Y') ?> <?= e(config('app.name')) ?> &middot; Wholesale fabric ordering
    </footer>
</body>
</html>
