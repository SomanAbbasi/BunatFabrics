<?php

declare(strict_types=1);

/** @var list<array{label: string, value: int, note: string}> $stats */
/** @var array<string, mixed> $user */
?>
<div class="row row-deck g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
        <div class="col-sm-6 col-xl-3"><article class="card w-100"><div class="card-body">
            <h2 class="subheader"><?= e($stat['label']) ?></h2>
            <div class="h1 mb-1"><?= e(number_format($stat['value'])) ?></div>
            <p class="text-secondary mb-0"><?= e($stat['note']) ?></p>
        </div></article></div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <h2 class="h3 mb-3">Catalog</h2>
        <div class="row row-deck g-3">
            <div class="col-md-6"><section class="card w-100">
                <div class="card-header"><h3 class="card-title">Categories</h3><a class="btn btn-sm btn-outline-primary ms-auto" href="<?= e(url('/admin/categories')) ?>">Manage</a></div>
                <div class="card-body"><p class="fw-medium">Organize fabric collections.</p><p class="text-secondary mb-0">Create categories, control visibility, and keep the storefront tidy.</p></div>
            </section></div>
            <div class="col-md-6"><section class="card w-100">
                <div class="card-header"><h3 class="card-title">Products</h3><a class="btn btn-sm btn-outline-primary ms-auto" href="<?= e(url('/admin/products')) ?>">Manage</a></div>
                <div class="card-body"><p class="fw-medium">Add photos, prices, and specifications.</p><p class="text-secondary mb-0">Published fabrics in visible categories appear on the storefront.</p></div>
            </section></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a class="btn btn-primary" href="<?= e(url('/admin/products/create')) ?>">Add product</a>
            <a class="btn" href="<?= e(url('/admin/categories/create')) ?>">Add category</a>
            <a class="btn" href="<?= e(url('/')) ?>">View storefront</a>
        </div>
    </div>
    <div class="col-xl-4">
        <h2 class="h3 mb-3">Account</h2>
        <section class="card"><div class="card-body">
            <h3 class="h2 mb-1"><?= e($user['name']) ?></h3>
            <span class="badge bg-green-lt mb-4">Administrator</span>
            <dl class="mb-0 admin-details">
                <dt class="text-secondary small">Email address</dt><dd class="mb-3"><?= e($user['email']) ?></dd>
                <dt class="text-secondary small">Member since</dt><dd class="mb-0"><?= e($user['created_at']) ?></dd>
            </dl>
        </div></section>
    </div>
</div>
