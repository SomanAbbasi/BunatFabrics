<?php

declare(strict_types=1);

use App\Core\Session;

?>
<div class="card"><div class="card-body">
    <p class="text-secondary">Use a stable slug such as <code>cotton-fabrics</code>. Hiding a category also hides its products from the public catalog.</p>
    <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><p class="mb-1">Please correct the following:</p><ul class="mb-0"><?php foreach ($errors as $field => $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST" action="<?= e(url('/admin/categories/' . ($id === null ? 'store' : 'update'))) ?>">
        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
        <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= e($id) ?>"><?php endif; ?>
        <div class="row g-3">
            <?php foreach (['name' => ['Name', 100], 'slug' => ['Slug', 120]] as $field => [$label, $limit]): ?>
                <div class="col-md-6"><label class="form-label required" for="<?= e($field) ?>"><?= e($label) ?></label>
                    <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>" id="<?= e($field) ?>" name="<?= e($field) ?>" maxlength="<?= e($limit) ?>" required value="<?= e($formData[$field] ?? '') ?>" aria-describedby="<?= e($field) ?>-error" <?= isset($errors[$field]) ? 'aria-invalid="true"' : '' ?>>
                    <div id="<?= e($field) ?>-error" class="invalid-feedback"><?= e($errors[$field] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
            <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="4" maxlength="5000" aria-describedby="description-error"><?= e($formData['description'] ?? '') ?></textarea><div class="invalid-feedback" id="description-error"><?= e($errors['description'] ?? '') ?></div></div>
            <div class="col-md-6"><label class="form-label" for="is_active">Visibility</label><select class="form-select" id="is_active" name="is_active"><option value="1" <?= (string) ($formData['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Visible</option><option value="0" <?= (string) ($formData['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Hidden</option></select></div>
        </div>
        <div class="admin-form-actions"><button class="btn btn-primary">Save Category</button><a class="btn" href="<?= e(url('/admin/categories')) ?>">Cancel</a></div>
    </form>
</div></div>
