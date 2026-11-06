<?php

declare(strict_types=1);

use App\Core\Session;

/** @var array<string, mixed> $user */
/** @var array<string, string> $errors */
$errors = $profileErrors ?? [];
$profile = $profile ?? $user;
?>
<?php if ($message = ($profileErrors['_form'] ?? $passwordErrors['_form'] ?? null)): ?>
    <div class="alert alert-danger" role="alert"><?= e($message) ?></div>
<?php endif; ?>
<div class="row g-4">
    <!-- Profile Information Section -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title mb-0">Profile Information</h2>
            </div>
            <div class="card-body">
                <form action="<?= e(url('/admin/settings/profile')) ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label required" for="settings-name">Administrator Name</label>
                        <input type="text" name="name" id="settings-name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e((string) $profile['name']) ?>" required autocomplete="name">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" for="settings-email">Email Address</label>
                        <input type="email" name="email" id="settings-email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e((string) $profile['email']) ?>" required autocomplete="email">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Used for administrator sign-in.</div>
                    </div>

                    <div class="mb-4 pt-2 border-top">
                        <div class="row g-2">
                            <div class="col-6">
                                <span class="text-secondary small d-block">Role</span>
                                <span class="badge bg-green-lt">Administrator</span>
                            </div>
                            <div class="col-6">
                                <span class="text-secondary small d-block">Member Since</span>
                                <span class="small fw-medium"><?= e(date('M d, Y', strtotime((string) $user['created_at']))) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" for="profile-current-password">Current password</label>
                        <input type="password" name="current_password" id="profile-current-password" class="form-control" required autocomplete="current-password">
                        <div class="form-text">Confirm your password before changing your name or email.</div>
                        <?php if (isset($profileErrors['current_password'])): ?><div class="text-danger"><?= e($profileErrors['current_password']) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        Save Profile Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Security & Password Reset Section -->
    <?php $errors = $passwordErrors ?? []; ?>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h2 class="card-title mb-0">Change Password</h2>
            </div>
            <div class="card-body">
                <form action="<?= e(url('/admin/settings/password')) ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label required" for="settings-current_password">Current Password</label>
                        <input type="password" name="current_password" id="settings-current_password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" required autocomplete="current-password">
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['current_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required" for="settings-new_password">New Password</label>
                        <input type="password" name="new_password" id="settings-new_password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['new_password']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Use 8+ characters with an uppercase letter, a lowercase letter and a number (up to 72 bytes).</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label required" for="settings-new_password_confirmation">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" id="settings-new_password_confirmation" class="form-control <?= isset($errors['new_password_confirmation']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
                        <?php if (isset($errors['new_password_confirmation'])): ?>
                            <div class="invalid-feedback"><?= e($errors['new_password_confirmation']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
