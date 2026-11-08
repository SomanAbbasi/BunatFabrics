<?php declare(strict_types=1); use App\Core\Session; ?>
<section class="auth auth-single"><div class="panel auth-card">
    <header class="auth-head"><h1>Reset password</h1><p class="muted">Use at least 8 characters with uppercase, lowercase and a number.</p></header>
    <?php foreach ($errors as $error): ?><p class="alert alert-error" role="alert"><?= e($error) ?></p><?php endforeach; ?>
    <form action="<?= e(url('/reset-password')) ?>" method="post">
        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
        <?php foreach (['password' => 'New password', 'password_confirmation' => 'Confirm password'] as $field => $label): ?>
        <div class="field"><label for="<?= e($field) ?>"><?= e($label) ?></label><div class="password-field">
            <input id="<?= e($field) ?>" type="password" name="<?= e($field) ?>" autocomplete="new-password" required minlength="8" maxlength="72">
            <button type="button" class="toggle-password" data-toggle-password="<?= e($field) ?>" aria-controls="<?= e($field) ?>" aria-label="Show password" aria-pressed="false">Show</button>
        </div></div>
        <?php endforeach; ?>
        <button class="button" type="submit">Save new password</button>
    </form>
    <p class="auth-foot center"><a href="<?= e(url('/forgot-password')) ?>">Request another reset link</a></p>
</div></section>
