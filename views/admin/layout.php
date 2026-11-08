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
    <link rel="icon" type="image/png" sizes="64x64" href="<?= e(url('/assets/images/bunat-logo.png')) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> &middot; Bunat Fabrics Admin</title>
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/tabler/tabler.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/admin.css?v=' . filemtime(base_path('public/assets/css/admin.css')))) ?>">
    <?php if (! empty($settingsPage)): ?><link rel="stylesheet" href="<?= e(url('/assets/css/settings.css')) ?>"><?php endif; ?>
    <script src="<?= e(url('/assets/vendor/tabler/tabler.min.js')) ?>" defer></script>
    <script src="<?= e(url('/assets/js/app.js')) ?>" defer></script>
</head>
<body class="admin-shell<?= ! empty($dashboardPage) ? ' admin-business-page' : '' ?>">
<a class="admin-skip" href="#main-content">Skip to content</a>
<div class="page">
    <aside class="navbar navbar-vertical navbar-expand-lg" aria-label="Admin sidebar">
        <div class="container-fluid">
            <a class="navbar-brand admin-brand" href="<?= e(url('/admin')) ?>">
                <img class="admin-brand-logo" src="<?= e(url('/assets/images/bunat-logo.png')) ?>" alt="" width="36" height="36">
                <span>Bunat Fabrics<small class="d-block text-secondary">Administration</small></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#admin-menu" aria-controls="admin-menu" aria-expanded="false" aria-label="Toggle admin navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="admin-menu">
                <nav aria-label="Admin navigation" class="w-100">
                    <ul class="navbar-nav pt-lg-3">
                        <?php foreach (['dashboard' => 'Dashboard', 'orders' => 'Orders', 'categories' => 'Categories', 'products' => 'Products', 'settings' => 'Settings'] as $section => $label): ?>
                            <li class="nav-item <?= $activeNav === $section ? 'active' : '' ?>"><a class="nav-link" href="<?= e(url($section === 'dashboard' ? '/admin' : '/admin/' . $section)) ?>" <?= $activeNav === $section ? 'aria-current="page"' : '' ?>><span class="nav-link-title"><?= e($label) ?></span></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </aside>
    <header class="navbar navbar-expand-md d-print-none admin-topbar">
        <div class="container-xl gap-3">
            <span class="text-secondary">Administration / <span class="text-body"><?= e($title) ?></span></span>
            <nav class="ms-auto d-flex align-items-center gap-3" aria-label="Account navigation">
                <?php $profileUser = $user; $profileShowStore = true; require base_path('views/partials/profile-menu.php'); ?>
            </nav>
        </div>
    </header>
    <div class="page-wrapper">
        <main id="main-content" tabindex="-1">
            <?php if (empty($dashboardPage)): ?>
                <div class="page-header"><div class="container-xl"><h1 class="page-title"><?= e($title) ?></h1></div></div>
            <?php endif; ?>
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
