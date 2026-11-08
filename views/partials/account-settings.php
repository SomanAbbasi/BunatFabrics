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
    <?php if (!$hasPassword): ?>
        <?php require __DIR__ . '/settings-signin-methods.php'; ?>
    <?php endif; ?>
    <section class="<?= e($panelClass) ?> settings-panel" aria-labelledby="profile-heading">
        <header class="settings-panel-heading">
            <h2 id="profile-heading">Profile details</h2>
            <p><?= $hasPassword ? 'Use your current password to update your name or email.' : 'Verify with Google, then update your name or email.' ?></p>
        </header>
        <form class="settings-form" action="<?= e(url($settingsPath . '/profile')) ?>" method="POST">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
            <?php if ($profileErrors !== []): ?><div class="settings-notice" role="alert"><?= e($profileErrors['_form'] ?? 'Your profile was not saved. Check the fields below.') ?></div><?php endif; ?>
            <?php foreach (['name' => ['Name', 'text', 100], 'email' => ['Email address', 'email', 190]] as $name => [$label, $type, $limit]): ?>
                <?php
                $descriptionIds = $name === 'email' ? ['profile-email-help'] : [];
                if (isset($profileErrors[$name])) {
                    $descriptionIds[] = 'profile-' . $name . '-error';
                }
                ?>
                <div class="settings-field">
                    <label for="profile-<?= e($name) ?>"><?= e($label) ?></label>
                    <input class="form-control" type="<?= e($type) ?>" id="profile-<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($profile[$name]) ?>" autocomplete="<?= e($name) ?>" required maxlength="<?= e((string) $limit) ?>" <?= $name === 'name' ? 'minlength="2"' : '' ?> <?= isset($profileErrors[$name]) ? 'aria-invalid="true"' : '' ?> <?= $descriptionIds !== [] ? 'aria-describedby="' . e(implode(' ', $descriptionIds)) . '"' : '' ?>>
                    <?php if ($name === 'email'): ?><p class="settings-help" id="profile-email-help">Your sign-in address. We will send a code to verify any new address.</p><?php endif; ?>
                    <?php if (isset($profileErrors[$name])): ?><p class="settings-error" id="profile-<?= e($name) ?>-error"><?= e($profileErrors[$name]) ?></p><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php
            $fieldErrors = $profileErrors;
            $field = ['id' => 'profile-password', 'name' => 'current_password', 'label' => 'Current password', 'autocomplete' => 'current-password'];
            if ($hasPassword) { require __DIR__ . '/settings-password-field.php'; }
            ?>
            <?php if (!$hasPassword && isset($profileErrors['current_password'])): ?><p class="settings-error" role="alert"><?= e($profileErrors['current_password']) ?></p><?php endif; ?>
            <div class="settings-actions"><button class="<?= e($buttonClass) ?>" type="submit">Save profile</button></div>
        </form>
        <?php if ($pendingEmail || $emailErrors !== []): ?>
        <section class="settings-email-verification" aria-labelledby="email-verification-heading">
            <header class="settings-panel-heading"><h3 id="email-verification-heading">Verify new email</h3><p>Your current email stays active until verification succeeds.</p></header>
            <form class="settings-form" action="<?= e(url($settingsPath . '/email/verify')) ?>" method="post">
                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                <?php foreach ($emailErrors as $error): ?><p class="settings-notice" role="alert"><?= e($error) ?></p><?php endforeach; ?>
                <p class="settings-help">Enter the code sent to <?= e($pendingEmail ?? 'your requested address') ?>. It expires in 10 minutes. You have five attempts.</p>
                <div class="settings-field"><label for="email-code">Verification code</label><input class="form-control" id="email-code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required></div>
                <div class="settings-actions"><button class="<?= e($buttonClass) ?>" type="submit">Verify email</button></div>
                <p class="settings-help">Need another code? Wait one minute, then submit the new address in Profile details again.</p>
            </form>
        </section>
        <?php endif; ?>
    </section>

    <section class="<?= e($panelClass) ?> settings-panel" aria-labelledby="password-heading">
        <header class="settings-panel-heading">
            <h2 id="password-heading"><?= $hasPassword ? 'Change password' : 'Set a password' ?></h2>
            <p><?= $hasPassword ? 'Choose a password you do not use elsewhere.' : 'Add a password to sign in with your email as well as Google.' ?></p>
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
            <p class="settings-help"><?= $hasPassword ? 'You will stay signed in after saving.' : 'Verify with Google above before saving. Google sign-in will remain available.' ?></p>
            <?php if (!$hasPassword && isset($passwordErrors['current_password'])): ?><p class="settings-error" role="alert"><?= e($passwordErrors['current_password']) ?></p><?php endif; ?>
            <div class="settings-actions"><button class="<?= e($buttonClass) ?>" type="submit"><?= $hasPassword ? 'Change password' : 'Set password' ?></button></div>
        </form>
    </section>
    <?php if ($hasPassword && ($googleLinked || App\Auth\GoogleOidc::enabled())): ?>
        <?php require __DIR__ . '/settings-signin-methods.php'; ?>
    <?php endif; ?>
</div>
