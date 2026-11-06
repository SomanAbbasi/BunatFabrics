<?php

declare(strict_types=1);

use App\Core\Session;

/** @var array<string, mixed> $user */
/** @var array<string, string> $errors */
$errors = $errors ?? [];
?>
<div class="row g-4">
    <!-- Profile Information Section -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Profile Information</h3>
            </div>
            <div class="card-body">
                <form action="<?= e(url('/admin/settings/profile')) ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label required">Administrator Name</label>
                        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= e((string) $user['name']) ?>" required autocomplete="name">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Email Address</label>
                        <input type="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= e((string) $user['email']) ?>" required autocomplete="email">
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Used for administrator sign-in and system notifications.</div>
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

                    <button type="submit" class="btn btn-primary">
                        Save Profile Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Security & Password Reset Section -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Change Password</h3>
            </div>
            <div class="card-body">
                <form action="<?= e(url('/admin/settings/password')) ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">

                    <div class="mb-3">
                        <label class="form-label required">Current Password</label>
                        <input type="password" name="current_password" class="form-control <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>" required autocomplete="current-password">
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['current_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">New Password</label>
                        <input type="password" name="new_password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback"><?= e($errors['new_password']) ?></div>
                        <?php endif; ?>
                        <div class="form-text">Must be at least 8 characters long.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label required">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" class="form-control <?= isset($errors['new_password_confirmation']) ? 'is-invalid' : '' ?>" required minlength="8" autocomplete="new-password">
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
