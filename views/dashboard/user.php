<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
?>
<section class="account-page">
    <header class="page-heading">
        <span class="eyebrow">Customer account</span>
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p>Your account details are below. Browse the catalog any time to view fabrics and wholesale pricing.</p>
    </header>

    <div class="account-grid">
        <article class="panel">
            <h2>Account details</h2>
            <dl class="detail-list">
                <div><dt>Name</dt><dd><?= e($user['name']) ?></dd></div>
                <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
                <div><dt>Member since</dt><dd><?= e($user['created_at']) ?></dd></div>
                <div><dt>Account type</dt><dd><?= e(ucfirst((string) $user['role'])) ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <h2>Start an order</h2>
            <p class="muted">Browse fabrics by category, check specifications, and view prices in PKR.</p>
            <a class="button button-compact" href="<?= e(url('/')) ?>">Browse fabrics</a>
        </article>
    </div>
</section>
