<?php if (App\Auth\GoogleOidc::enabled()): ?>
<form action="<?= e(url('/auth/google')) ?>" method="post" class="google-signin">
    <input type="hidden" name="_token" value="<?= e(App\Core\Session::csrfToken()) ?>">
    <input type="hidden" name="mode" value="login">
    <button type="submit" class="button button-ghost">Continue with Google</button>
</form>
<p class="muted center">or use your email and password</p>
<?php endif; ?>
