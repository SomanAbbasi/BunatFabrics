<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array{users: int, admins: int} $stats */

?>
<section class="dashboard">
    <div class="hero hero-admin">
        <span class="badge">Administrator</span>
        <h1>Hello, I'm an admin</h1>
        <p class="muted">
            Welcome back, <?= e($user['name']) ?>. This is the Bunat Fabrics admin dashboard.
        </p>
    </div>

    <div class="stat-grid">
        <article class="card stat">
            <span class="stat-value"><?= e((string) $stats['users']) ?></span>
            <span class="stat-label">Registered users</span>
        </article>

        <article class="card stat">
            <span class="stat-value"><?= e((string) $stats['admins']) ?></span>
            <span class="stat-label">Administrators</span>
        </article>

        <article class="card stat">
            <span class="stat-value"><?= e(strtoupper((string) $user['role'])) ?></span>
            <span class="stat-label">Your role</span>
        </article>
    </div>

    <article class="card">
        <h2>Signed in as</h2>
        <dl class="detail-list">
            <div>
                <dt>Name</dt>
                <dd><?= e($user['name']) ?></dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd><?= e($user['email']) ?></dd>
            </div>
            <div>
                <dt>Member since</dt>
                <dd><?= e($user['created_at']) ?></dd>
            </div>
        </dl>
    </article>
</section>
