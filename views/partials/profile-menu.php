<?php

declare(strict_types=1);

use App\Core\Session;

/** @var array<string, mixed>|null $profileUser */
if (! is_array($profileUser ?? null)) {
    return;
}

$profileIsAdmin = ($profileUser['role'] ?? '') === 'admin';
$profileName = (string) ($profileUser['name'] ?? 'Account');
$profileInitial = mb_strtoupper(mb_substr($profileName, 0, 1));
?>
<div class="profile">
    <button type="button" class="profile-toggle" data-profile-toggle aria-expanded="false" aria-controls="profile-menu" aria-label="Open account menu">
        <span class="profile-avatar" aria-hidden="true"><?= e($profileInitial) ?></span>
        <span class="profile-name"><?= e($profileName) ?></span>
        <svg class="profile-chevron" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 8 4 4 4-4"/></svg>
    </button>

    <div class="profile-menu" id="profile-menu" hidden>
        <div class="profile-menu-head">
            <strong><?= e($profileName) ?></strong>
            <span><?= e($profileUser['email'] ?? '') ?></span>
            <?php if ($profileIsAdmin): ?><span class="profile-role">Administrator</span><?php endif; ?>
        </div>
        <div class="profile-menu-links">
            <?php if (! empty($profileShowStore)): ?><a href="<?= e(url('/')) ?>">View store</a><?php endif; ?>
            <a href="<?= e(url('/dashboard')) ?>">Dashboard</a>
            <a href="<?= e(url('/orders')) ?>">My Orders</a>
            <?php if ($profileIsAdmin): ?><a href="<?= e(url('/admin')) ?>">Admin dashboard</a><?php endif; ?>
        </div>
        <form action="<?= e(url('/logout')) ?>" method="POST" class="profile-menu-form">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
            <button type="submit">Sign out</button>
        </form>
    </div>
</div>
