<?php

declare(strict_types=1);

/** @var array<string, int> $stats */
/** @var array<string, mixed> $user */
?>
<div class="row row-deck g-3 mb-4">
    <?php foreach ($stats as $label => $count): ?>
        <div class="col-sm-6 col-lg-4"><article class="card w-100"><div class="card-body">
            <h2 class="subheader"><?= e($label) ?></h2>
            <div class="h1 mb-1"><?= e(number_format($count)) ?></div>
            <p class="text-secondary mb-0">Current registered accounts</p>
        </div></article></div>
    <?php endforeach; ?>
</div>
<div class="row g-4">
    <div class="col-xl-8">
        <h2 class="h3 mb-3">Your workspace</h2>
        <div class="row row-deck g-3">
            <?php foreach ([
                'categories' => ['Categories', 'Organize your fabric collections.', 'Create, edit, and control the visibility of your fabric categories.'],
                'products' => ['Products', 'Keep your fabric catalog in one place.', 'Create fabrics and manage their publication status in the catalog.'],
                'orders' => ['Orders', 'Follow your wholesale orders.', 'Review orders and manage their progress.'],
                'settings' => ['Settings', 'Manage your administrator account.', 'Update your administrator profile and password.'],
            ] as $id => [$heading, $summary, $message]): ?>
                <div class="col-md-6"><section class="card w-100 admin-feature" id="<?= e($id) ?>" aria-labelledby="<?= e($id) ?>-title" tabindex="-1">
                    <div class="card-header"><h3 class="card-title" id="<?= e($id) ?>-title"><?= e($heading) ?></h3><?php if (in_array($id, ['categories', 'products', 'orders', 'settings'], true)): ?><a class="btn btn-sm btn-outline-primary ms-auto" href="<?= e(url('/admin/' . $id)) ?>">Manage</a><?php else: ?><span class="badge bg-secondary-lt ms-auto">Coming soon</span><?php endif; ?></div>
                    <div class="card-body"><p class="fw-medium"><?= e($summary) ?></p><p class="text-secondary mb-0"><?= e($message) ?></p></div>
                </section></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-xl-4">
        <h2 class="h3 mb-3">Administrator profile</h2>
        <section class="card"><div class="card-body">
            <span class="avatar avatar-lg admin-monogram mb-3" aria-hidden="true">B</span>
            <h3 class="h2 mb-1"><?= e($user['name']) ?></h3>
            <span class="badge bg-green-lt mb-4">Administrator</span>
            <dl class="mb-0 admin-details">
                <dt class="text-secondary small">Email address</dt><dd class="mb-3"><?= e($user['email']) ?></dd>
                <dt class="text-secondary small">Member since</dt><dd class="mb-0"><?= e($user['created_at']) ?></dd>
            </dl>
        </div></section>
    </div>
</div>
