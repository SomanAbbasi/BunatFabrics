<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;

/** @var array<string, mixed> $formData */
/** @var array<string, string> $errors */
/** @var array<int, array<string, mixed>> $images */
/** @var array<int, array<string, mixed>> $categories */
?>
<?php if ($categories === []): ?><div class="alert alert-info">Create a category before saving a product. <a href="<?= e(url('/admin/categories/create')) ?>">Add Category</a></div><?php endif; ?>
<div class="card"><div class="card-body">
    <p class="text-secondary">Add the fabric details, wholesale price in PKR, and product photos. The first photo becomes the catalog cover.</p>
    <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert"><p class="mb-1">Please correct the following:</p><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST" action="<?= e(url('/admin/products/' . ($id === null ? 'store' : 'update'))) ?>" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
        <?php if ($id !== null): ?><input type="hidden" name="id" value="<?= e($id) ?>"><?php endif; ?>
        <div class="row g-3">
            <?php foreach (['product_code' => ['Product code', 50, true], 'title' => ['Title', 150, true], 'composition' => ['Composition (optional)', 100, false]] as $field => [$label, $limit, $required]): ?>
                <div class="col-md-6"><label class="form-label <?= $required ? 'required' : '' ?>" for="<?= e($field) ?>"><?= e($label) ?></label>
                    <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>" id="<?= e($field) ?>" name="<?= e($field) ?>" maxlength="<?= e($limit) ?>" <?= $required ? 'required' : '' ?> value="<?= e($formData[$field] ?? '') ?>" aria-describedby="<?= e($field) ?>-error" <?= isset($errors[$field]) ? 'aria-invalid="true"' : '' ?>>
                    <div id="<?= e($field) ?>-error" class="invalid-feedback"><?= e($errors[$field] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
            <div class="col-md-6"><label class="form-label required" for="category_id">Category</label>
                <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" name="category_id" id="category_id" required aria-describedby="category-error category-help">
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $category): ?><option value="<?= e($category['id']) ?>" <?= (string) ($formData['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?><?= $category['is_active'] ? '' : ' (hidden)' ?></option><?php endforeach; ?>
                </select><div class="invalid-feedback" id="category-error"><?= e($errors['category_id'] ?? '') ?></div><small class="form-hint" id="category-help">Products in hidden categories stay off the public homepage, even when published.</small>
            </div>
            <div class="col-12"><label class="form-label required" for="description">Description</label><textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" name="description" id="description" rows="5" maxlength="5000" required aria-describedby="description-error"><?= e($formData['description'] ?? '') ?></textarea><div class="invalid-feedback" id="description-error"><?= e($errors['description'] ?? '') ?></div></div>
            <div class="col-md-4"><label class="form-label required" for="price_pkr">Price (PKR)</label><div class="input-group"><span class="input-group-text">PKR</span><input class="form-control <?= isset($errors['price_pkr']) ? 'is-invalid' : '' ?>" type="number" name="price_pkr" id="price_pkr" min="0" max="9999999999.99" step="0.01" required value="<?= e($formData['price_pkr'] ?? '0') ?>" aria-describedby="price-error"><div class="invalid-feedback" id="price-error"><?= e($errors['price_pkr'] ?? '') ?></div></div></div>
            <?php foreach (['width_inch' => ['Width in inches (optional)', '0.1', '9999.9', '0.1'], 'weight_gsm' => ['Weight in GSM (optional)', '1', '65535', '1']] as $field => [$label, $min, $max, $step]): ?>
                <div class="col-md-4"><label class="form-label" for="<?= e($field) ?>"><?= e($label) ?></label><input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>" type="number" name="<?= e($field) ?>" id="<?= e($field) ?>" min="<?= e($min) ?>" max="<?= e($max) ?>" step="<?= e($step) ?>" value="<?= e($formData[$field] ?? '') ?>" aria-describedby="<?= e($field) ?>-error"><div class="invalid-feedback" id="<?= e($field) ?>-error"><?= e($errors[$field] ?? '') ?></div></div>
            <?php endforeach; ?>
            <?php foreach (['unit' => ['Unit', ['meter' => 'Meter', 'yard' => 'Yard']], 'status' => ['Publication status', ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']]] as $field => [$label, $options]): ?>
                <div class="col-md-6"><label class="form-label" for="<?= e($field) ?>"><?= e($label) ?></label><select class="form-select" name="<?= e($field) ?>" id="<?= e($field) ?>"><?php foreach ($options as $value => $text): ?><option value="<?= e($value) ?>" <?= ($formData[$field] ?? '') === $value ? 'selected' : '' ?>><?= e($text) ?></option><?php endforeach; ?></select></div>
            <?php endforeach; ?>
            <div class="col-12">
                <label class="form-label" for="images">Product photos</label>
                <input class="form-control <?= isset($errors['images']) ? 'is-invalid' : '' ?>" type="file" name="images[]" id="images" accept="image/jpeg,image/png,image/webp" multiple aria-describedby="images-help images-error">
                <div class="invalid-feedback" id="images-error"><?= e($errors['images'] ?? '') ?></div>
                <small class="form-hint" id="images-help">JPG, PNG, or WebP. Up to 6 images, 2 MB each. Images are stored by category and product.</small>
            </div>
        </div>
        <div class="admin-form-actions"><button class="btn btn-primary" <?= $categories === [] ? 'disabled' : '' ?>>Save Product</button><a class="btn" href="<?= e(url('/admin/products')) ?>">Cancel</a></div>
    </form>

    <?php if ($images !== []): ?>
        <hr class="my-4">
        <h2 class="h3">Current photos</h2>
        <div class="product-image-grid">
            <?php foreach ($images as $index => $image): ?>
                <figure class="product-image-tile">
                    <img src="<?= e(ProductImageService::url((string) $image['file_path'])) ?>" alt="<?= e($formData['title'] ?? 'Product') ?> photo <?= e((string) ($index + 1)) ?>">
                    <figcaption>
                        <?php if ($index === 0): ?><span class="badge bg-green-lt">Cover</span><?php else: ?><span class="text-secondary small">Photo <?= e((string) ($index + 1)) ?></span><?php endif; ?>
                        <form method="POST" action="<?= e(url('/admin/products/images/delete')) ?>" onsubmit="return confirm('Remove this photo?');">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <input type="hidden" name="product_id" value="<?= e((string) $id) ?>">
                            <input type="hidden" name="image_id" value="<?= e((string) $image['id']) ?>">
                            <button class="btn btn-sm btn-link text-danger p-0" type="submit">Remove</button>
                        </form>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></div>
