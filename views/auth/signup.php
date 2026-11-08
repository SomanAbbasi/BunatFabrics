<?php

declare(strict_types=1);

use App\Core\Session;

/** @var array<string, string> $errors */

?>
<section class="auth auth-single">
    <div class="panel auth-card">
        <header class="auth-head">
            <span class="brand-mark brand-mark-lg" aria-hidden="true">B</span>
            <span class="eyebrow">Bunat Fabrics</span>
            <h1>Create account</h1>
            <p class="muted">Open a wholesale account to browse fabrics.</p>
        </header>

        <?php require base_path('views/partials/google-button.php'); ?>

        <form action="<?= e(url('/signup')) ?>" method="POST">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">

            <div class="field">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= e(old('name')) ?>" placeholder="Your name" autocomplete="name" required>
                <?php if (isset($errors['name'])): ?><span class="field-error"><?= e($errors['name']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="you@company.com" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="toggle-password" data-toggle-password="password" aria-label="Show password" aria-pressed="false">
                        <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a17.7 17.7 0 0 1-3.1 4.2"/><path d="M6.7 6.7A17.6 17.6 0 0 0 2 12s3.6 7 10 7a10.9 10.9 0 0 0 4.3-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                <span class="field-hint">8+ characters with an uppercase letter, a lowercase letter and a number.</span>
                <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <div class="password-field">
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repeat your password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="toggle-password" data-toggle-password="password_confirmation" aria-label="Show password" aria-pressed="false">
                        <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a17.7 17.7 0 0 1-3.1 4.2"/><path d="M6.7 6.7A17.6 17.6 0 0 0 2 12s3.6 7 10 7a10.9 10.9 0 0 0 4.3-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                <?php if (isset($errors['password_confirmation'])): ?><span class="field-error"><?= e($errors['password_confirmation']) ?></span><?php endif; ?>
            </div>

            <button type="submit" class="button">Create account</button>
        </form>

        <p class="muted center auth-foot">Already registered? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
    </div>
</section>
