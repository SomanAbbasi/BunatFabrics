<?php declare(strict_types=1); use App\Core\Session; $authPage = true; ?>
<section class="auth auth-single"><div class="panel auth-card">
    <header class="auth-head"><h1>Forgot password?</h1><p class="muted">Enter your account email to request a password reset link.</p></header>
    <form action="<?= e(url('/forgot-password')) ?>" method="post">
        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
        <div class="field"><label for="recovery-email">Email address</label><input id="recovery-email" type="email" name="email" autocomplete="email" required maxlength="190"></div>
        <button class="button" type="submit">Send reset link</button>
    </form>
    <p class="auth-foot center"><a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
</div></section>
