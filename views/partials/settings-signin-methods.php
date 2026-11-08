<?php

declare(strict_types=1);

/** Shared by the admin and customer settings page; all actions use existing routes. */
?>
<section class="<?= e($panelClass) ?> settings-panel settings-signin" aria-labelledby="signin-heading">
    <header class="settings-panel-heading">
        <h2 id="signin-heading"><?= $hasPassword ? 'Google sign-in' : 'Verify your Google account' ?></h2>
        <p><?= $hasPassword ? 'Manage how you sign in to Bunat Fabrics.' : 'Verify before editing your profile or setting a password. Verification lasts five minutes and is cleared after saving.' ?></p>
    </header>
    <?php if ($googleLinked && $hasPassword): ?>
        <div class="settings-signin-details">
            <p>Google is connected for sign-in.</p>
            <p class="settings-help">Use your current Bunat password to change account settings.</p>
        </div>
    <?php elseif (App\Auth\GoogleOidc::enabled()): ?>
        <form class="settings-form" action="<?= e(url('/auth/google')) ?>" method="post">
            <input type="hidden" name="_token" value="<?= e(App\Core\Session::csrfToken()) ?>">
            <input type="hidden" name="mode" value="<?= $googleLinked ? 'verify' : 'link' ?>">
            <?php if (!$googleLinked): ?>
                <?php
                $fieldErrors = [];
                $field = ['id' => 'google-link-password', 'name' => 'current_password', 'label' => 'Current password', 'autocomplete' => 'current-password'];
                require __DIR__ . '/settings-password-field.php';
                ?>
                <p class="settings-help">Confirm your password, then choose the Google account to connect.</p>
            <?php endif; ?>
            <div class="settings-actions"><button class="<?= e($buttonClass) ?>" type="submit"><?= $googleLinked ? 'Verify with Google' : 'Connect Google' ?></button></div>
        </form>
    <?php else: ?>
        <div class="settings-signin-details"><p class="settings-help">Google verification is currently unavailable. Contact the business for help.</p></div>
    <?php endif; ?>
</section>
