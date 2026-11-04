<?php

declare(strict_types=1);
use App\Core\Session;
?>
<?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="row g-4"><section class="col-lg-6"><div class="card"><div class="card-body">
<h2 class="card-title">Administrator profile</h2><p class="text-secondary">Update the name and email used to sign in.</p>
<form method="post" action="<?= e(url('/admin/settings/profile')) ?>"><input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
<div class="mb-3"><label class="form-label required" for="name">Name</label><input class="form-control" id="name" name="name" required maxlength="100" value="<?= e($profile['name']) ?>"></div>
<div class="mb-3"><label class="form-label required" for="email">Email</label><input class="form-control" id="email" name="email" type="email" required maxlength="190" value="<?= e($profile['email']) ?>"></div>
<button class="btn btn-primary">Save profile</button></form></div></div></section>
<section class="col-lg-6"><div class="card"><div class="card-body"><h2 class="card-title">Change password</h2><p class="text-secondary">Use at least eight characters, including uppercase, lowercase, and a number.</p>
<form method="post" action="<?= e(url('/admin/settings/password')) ?>"><input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
<?php foreach (['current_password'=>'Current password','password'=>'New password','password_confirmation'=>'Confirm new password'] as $field=>$label): ?><div class="mb-3"><label class="form-label required" for="<?= e($field) ?>"><?= e($label) ?></label><input class="form-control" type="password" id="<?= e($field) ?>" name="<?= e($field) ?>" autocomplete="<?= $field === 'current_password' ? 'current-password' : 'new-password' ?>" required></div><?php endforeach; ?>
<button class="btn btn-primary">Update password</button></form></div></div></section></div>
