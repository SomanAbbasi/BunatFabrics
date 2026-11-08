<?php

declare(strict_types=1);

use App\Core\Session;

/** @var array<string, mixed> $user */
/** @var string $settingsPath */
/** @var bool $settingsAdmin */
$hasPassword = $hasPassword ?? true;
$googleLinked = $googleLinked ?? false;
$pendingEmail = $pendingEmail ?? null;
$emailErrors = $emailErrors ?? [];
$profile = $profile ?? $user;
$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];
$panelClass = $settingsAdmin ? 'card' : 'panel';
$buttonClass = $settingsAdmin ? 'btn btn-primary' : 'button button-compact';
?>
<div class="account-settings">
    <?php if (!$hasPassword || $googleLinked || App\Auth\GoogleOidc::enabled()): ?>
    <section class="<?= e($panelClass) ?> settings-panel">
        <header class="settings-panel-heading"><h2>Sign-in methods</h2><p><?= $hasPassword ? 'Email and password' : 'Google account' ?><?= $googleLinked && $hasPassword ? ' · Google connected' : '' ?></p></header>
        <?php if (App\Auth\GoogleOidc::enabled()): ?>
        <form class="settings-form" action="<?= e(url('/auth/google')) ?>" method="post">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
            <input type="hidden" name="mode" value="<?= $googleLinked ? 'verify' : 'link' ?>">
            <?php if (!$googleLinked): ?>
            <?php $fieldErrors = []; $field = ['id'=>'google-link-password','name'=>'current_password','label'=>'Current password','autocomplete'=>'current-password']; require __DIR__ . '/settings-password-field.php'; ?>
            <?php endif; ?>
            <p class="settings-help"><?= $googleLinked ? 'Google-only accounts must verify again before changing their profile or setting a password. Verification lasts five minutes and is cleared after saving.' : 'Confirm your password, then choose the Google account to connect.' ?></p>
            <button class="<?= e($buttonClass) ?>" type="submit"><?= $googleLinked ? 'Verify with Google' : 'Connect Google' ?></button>
        </form>
        <?php else: ?><p class="settings-form">Google verification is currently unavailable. Contact the business for help.</p><?php endif; ?>
    </section>
    <?php endif; ?>
    <?php if ($pendingEmail || $emailErrors !== []): ?>
    <section class="<?= e($panelClass) ?> settings-panel">
        <header class="settings-panel-heading"><h2>Verify new email</h2><p>Your current email stays active until verification succeeds.</p></header>
        <form class="settings-form" action="<?= e(url($settingsPath . '/email/verify')) ?>" method="post">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
            <?php foreach ($emailErrors as $error): ?><p class="settings-notice" role="alert"><?= e($error) ?></p><?php endforeach; ?>
            <p class="settings-help">Enter the code sent to <?= e($pendingEmail ?? 'your requested address') ?>. It expires after 10 minutes and allows five attempts. To resend, submit your profile again after one minute.</p>
            <div class="settings-field"><label for="email-code">Verification code</label><input class="form-control" id="email-code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></div>
            <button class="<?= e($buttonClass) ?>" type="submit">Verify email</button>
        </form>
    </section>
    <?php endif; ?>
    <section class="<?= e($panelClass) ?> settings-panel" aria-labelledby="profile-heading">
        <header class="settings-panel-heading">
            <h2 id="profile-heading">Profile details</h2>
            <p><?= $hasPassword ? 'Confirm your current password to save changes.' : 'Verify with Google before saving changes.' ?> New email addresses must also be verified.</p>
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
            if ($hasPassword) { require __DIR__ . '/settings-password-field.php'; }
            ?>
            <?php if (!$hasPassword && isset($profileErrors['current_password'])): ?><p class="settings-error" role="alert"><?= e($profileErrors['current_password']) ?></p><?php endif; ?>
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
                if (!$hasPassword && $field['name'] === 'current_password') { continue; }
                require __DIR__ . '/settings-password-field.php';
            }
            ?>
            <p class="settings-help">You will stay signed in after changing your password.</p>
            <?php if (!$hasPassword && isset($passwordErrors['current_password'])): ?><p class="settings-error" role="alert"><?= e($passwordErrors['current_password']) ?></p><?php endif; ?>
            <div class="settings-actions"><button class="<?= e($buttonClass) ?>" type="submit">Change password</button></div>
        </form>
    </section>
</div>
