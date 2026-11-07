<?php

declare(strict_types=1);

use App\Core\Session;

/** @var array<string, mixed> $user */
/** @var string $settingsPath */
/** @var bool $settingsAdmin */
$profile = $profile ?? $user;
$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];
$panelClass = $settingsAdmin ? 'card' : 'panel';
$buttonClass = $settingsAdmin ? 'btn btn-primary' : 'button button-compact';
?>
<div class="account-settings">
    <section class="<?= e($panelClass) ?> settings-panel" aria-labelledby="profile-heading">
        <header class="settings-panel-heading">
            <h2 id="profile-heading">Profile details</h2>
            <p>Confirm your current password to change your name or email.</p>
        </header>
        <form class="settings-form" action="<?= e(url($settingsPath . '/profile')) ?>" method="POST">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
            <?php if ($profileErrors !== []): ?><div class="settings-notice" role="alert"><?= e($profileErrors['_form'] ?? 'Your profile was not saved. Check the fields below.') ?></div><?php endif; ?>
            <?php foreach (['name' => ['Name', 'text', 100], 'email' => ['Email address', 'email', 190]] as $name => [$label, $type, $limit]): ?>
                <div class="settings-field">
                    <label for="profile-<?= e($name) ?>"><?= e($label) ?></label>
                    <input class="form-control" type="<?= e($type) ?>" id="profile-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($profile[$name]) ?>" autocomplete="<?= e($name) ?>" required maxlength="<?= e((string) $limit) ?>" <?= $name === 'name' ? 'minlength="2"' : '' ?> <?= isset($profileErrors[$name]) ? 'aria-invalid="true" aria-describedby="profile-' . e($name) . '-error"' : '' ?>>
                    <?php if (isset($profileErrors[$name])): ?><p class="settings-error" id="profile-<?= e($name) ?>-error"><?= e($profileErrors[$name]) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <p class="settings-help">Use this email address to sign in.</p>
            <?php
            $fieldErrors = $profileErrors;
            $field = ['id' => 'profile-password', 'name' => 'current_password', 'label' => 'Current password', 'autocomplete' => 'current-password'];
            require __DIR__ . '/settings-password-field.php';
            ?>
            <div class="settings-actions"><button class="<?= e($buttonClass) ?>" type="submit">Save profile</button></div>
        </form>
    </section>

    <section class="<?= e($panelClass) ?> settings-panel" aria-labelledby="password-heading">
        <header class="settings-panel-heading">
            <h2 id="password-heading">Change password</h2>
            <p>Choose a new password that you do not use elsewhere.</p>
        </header>
        <form class="settings-form" action="<?= e(url($settingsPath . '/password')) ?>" method="POST">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
            <?php if ($passwordErrors !== []): ?><div class="settings-notice" role="alert"><?= e($passwordErrors['_form'] ?? 'Your password was not changed. Check the fields below.') ?></div><?php endif; ?>
            <?php
            $fieldErrors = $passwordErrors;
            $fields = [
                ['id' => 'security-current-password', 'name' => 'current_password', 'label' => 'Current password', 'autocomplete' => 'current-password'],
                ['id' => 'security-new-password', 'name' => 'new_password', 'label' => 'New password', 'autocomplete' => 'new-password', 'hint' => 'Use at least 8 characters, including an uppercase letter, a lowercase letter and a number.'],
                ['id' => 'security-confirm-password', 'name' => 'new_password_confirmation', 'label' => 'Confirm new password', 'autocomplete' => 'new-password'],
            ];
            foreach ($fields as $field) {
                require __DIR__ . '/settings-password-field.php';
            }
            ?>
            <p class="settings-help">You will stay signed in after changing your password.</p>
            <div class="settings-actions"><button class="<?= e($buttonClass) ?>" type="submit">Change password</button></div>
        </form>
    </section>
</div>
