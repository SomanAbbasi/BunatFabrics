<?php

declare(strict_types=1);

use App\Core\Session;

?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <p class="text-secondary mb-0">Only published fabrics in visible categories appear in the public catalog.</p>
    <a class="btn btn-primary" href="<?= e(url('/admin/products/create')) ?>">Add Product</a>
</div>
<div class="card">
    <?php if ($items === []): ?><div class="empty"><h2 class="empty-title">No products to show</h2><p class="empty-subtitle text-secondary">Create a category, then add your first fabric. Drafts stay private until published.</p></div>
    <?php else: ?><div class="table-responsive"><table class="table table-vcenter card-table">
        <caption class="visually-hidden">Fabrics and publication controls</caption>
        <thead><tr><th scope="col">Code</th><th scope="col">Product</th><th scope="col">Category</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
        <tbody><?php foreach ($items as $product): ?><tr>
            <td><?= e($product['product_code']) ?></td><td class="catalog-title"><?= e($product['title']) ?></td>
            <td class="catalog-title"><?= e($product['category_name']) ?><?php if (! $product['category_active']): ?><small class="d-block text-secondary">Hidden category</small><?php endif; ?></td>
            <td><span class="badge <?= $product['status'] === 'published' ? 'bg-green-lt' : 'bg-secondary-lt' ?>"><?= e(ucfirst($product['status'])) ?></span></td>
            <td><div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm" href="<?= e(url('/admin/products/edit?id=' . $product['id'])) ?>" aria-label="Edit <?= e($product['title']) ?>">Edit</a>
                <?php foreach (['published' => 'Publish', 'draft' => 'Unpublish / Draft', 'archived' => 'Archive'] as $state => $label): ?>
                    <?php if ($state !== $product['status']): ?><form method="POST" action="<?= e(url('/admin/products/state')) ?>">
                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><input type="hidden" name="id" value="<?= e($product['id']) ?>"><input type="hidden" name="status" value="<?= e($state) ?>">
                        <button class="btn btn-sm" aria-label="<?= e($label . ' ' . $product['title']) ?>"><?= e($label) ?></button>
                    </form><?php endif; ?>
                <?php endforeach; ?>
            </div></td>
        </tr><?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
</div>
<?php require base_path('views/admin/catalog-pagination.php'); ?>
