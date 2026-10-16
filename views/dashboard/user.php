<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */

?>
<section class="dashboard">
    <div class="hero">
        <span class="badge">Customer</span>
        <h1>Hello, <?= e($user['name']) ?></h1>
        <p class="muted">Welcome to your Bunat Fabrics dashboard.</p>
    </div>

    <article class="card">
        <h2>Your account</h2>
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
