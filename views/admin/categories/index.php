<?php

declare(strict_types=1);

use App\Core\Session;

?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <p class="text-secondary mb-0">Hidden categories and their products are excluded from the public catalog. Hide categories instead of deleting them.</p>
    <a class="btn btn-primary" href="<?= e(url('/admin/categories/create')) ?>">Add Category</a>
</div>
<div class="card">
    <?php if ($items === []): ?>
        <div class="empty"><h2 class="empty-title">No categories to show</h2><p class="empty-subtitle text-secondary">Add your first category to organize your fabrics.</p></div>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-vcenter card-table">
            <caption class="visually-hidden">Fabric categories and visibility controls</caption>
            <thead><tr><th scope="col">Category</th><th scope="col">Slug</th><th scope="col">Products</th><th scope="col">Visibility</th><th scope="col">Actions</th></tr></thead>
            <tbody><?php foreach ($items as $category): ?><tr>
                <td class="catalog-title"><?= e($category['name']) ?></td><td><?= e($category['slug']) ?></td>
                <td><?= e($category['product_count']) ?></td><td><span class="badge <?= $category['is_active'] ? 'bg-green-lt' : 'bg-secondary-lt' ?>"><?= $category['is_active'] ? 'Visible' : 'Hidden' ?></span></td>
                <td><div class="d-flex gap-2 align-items-center">
                    <a class="btn btn-sm" href="<?= e(url('/admin/categories/edit?id=' . $category['id'])) ?>" aria-label="Edit <?= e($category['name']) ?>">Edit</a>
                    <form method="POST" action="<?= e(url('/admin/categories/state')) ?>">
                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><input type="hidden" name="id" value="<?= e($category['id']) ?>">
                        <input type="hidden" name="is_active" value="<?= $category['is_active'] ? '0' : '1' ?>">
                        <button class="btn btn-sm" aria-label="<?= $category['is_active'] ? 'Hide' : 'Enable' ?> <?= e($category['name']) ?>"><?= $category['is_active'] ? 'Hide' : 'Enable' ?></button>
                    </form>
                </div></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php require base_path('views/admin/catalog-pagination.php'); ?>
