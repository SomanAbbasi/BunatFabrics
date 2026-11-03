<?php

declare(strict_types=1);

use App\Core\Session;

/** @var array<string, mixed> $user */
$activeNav = $activeNav ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> &middot; Bunat Fabrics Admin</title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/tabler/tabler.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/admin.css')) ?>">
    <script src="<?= e(url('/assets/vendor/tabler/tabler.min.js')) ?>" defer></script>
</head>
<body>
<a class="admin-skip" href="#main-content">Skip to content</a>
<div class="page">
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark" aria-label="Admin sidebar">
        <div class="container-fluid">
            <a class="navbar-brand admin-brand" href="<?= e(url('/admin')) ?>">
                <span class="avatar admin-monogram" aria-hidden="true">B</span>
                <span>Bunat Fabrics<small class="d-block text-secondary fs-5">Administration</small></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#admin-menu" aria-controls="admin-menu" aria-expanded="false" aria-label="Toggle admin navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="admin-menu">
                <nav aria-label="Admin navigation" class="w-100">
                    <ul class="navbar-nav pt-lg-3">
                        <?php foreach (['dashboard' => 'Dashboard', 'categories' => 'Categories', 'products' => 'Products'] as $section => $label): ?>
                            <li class="nav-item <?= $activeNav === $section ? 'active' : '' ?>"><a class="nav-link" href="<?= e(url($section === 'dashboard' ? '/admin' : '/admin/' . $section)) ?>" <?= $activeNav === $section ? 'aria-current="page"' : '' ?>><span class="nav-link-title"><?= e($label) ?></span></a></li>
                        <?php endforeach; ?>
                        <?php foreach (['orders' => 'Orders', 'settings' => 'Settings'] as $section => $label): ?>
                            <li class="nav-item"><a class="nav-link" href="<?= e(url('/admin') . '#' . $section) ?>"><span class="nav-link-title"><?= e($label) ?></span><span class="badge bg-secondary-lt ms-auto">Soon</span></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="admin-sidebar-note text-secondary px-3 py-4">B2B fabric wholesale<br>Built for your daily operations.</div>
                </nav>
            </div>
        </div>
    </aside>
    <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl gap-3">
            <span class="text-secondary">Administration / <span class="text-body"><?= e($title) ?></span></span>
            <nav class="ms-auto d-flex align-items-center gap-3" aria-label="Account navigation">
                <a class="text-secondary" href="<?= e(url('/')) ?>">View site</a>
                <div class="d-none d-md-block text-end"><div class="fw-semibold"><?= e($user['name']) ?></div><div class="text-secondary small">Administrator</div></div>
                <form action="<?= e(url('/logout')) ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Sign out</button>
                </form>
            </nav>
        </div>
    </header>
    <div class="page-wrapper">
        <main id="main-content" tabindex="-1">
            <div class="page-header"><div class="container-xl"><div class="page-pretitle">Bunat Fabrics</div><h1 class="page-title"><?= e($title) ?></h1><?php if ($activeNav === 'dashboard'): ?><p class="text-secondary mt-2">Welcome back, <?= e($user['name']) ?>. Here is your account overview.</p><?php endif; ?></div></div>
            <div class="page-body"><div class="container-xl">
                <?php if ($status = Session::pullFlash('status')): ?><div class="alert alert-success" role="status"><?= e($status) ?></div><?php endif; ?>
                <?php if ($error = Session::pullFlash('error')): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
                <?= $content ?>
            </div></div>
        </main>
        <footer class="footer footer-transparent"><div class="container-xl text-secondary small">&copy; <?= date('Y') ?> Bunat Fabrics &middot; Administration</div></footer>
    </div>
</div>
</body>
</html>
