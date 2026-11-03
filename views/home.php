<?php

declare(strict_types=1);

?>
<section class="dashboard catalog">
    <header class="hero"><span class="badge">Bunat Fabrics</span><h1>Explore our fabrics</h1><p class="muted">Browse our published fabric collections and specifications.</p></header>
    <?php if ($unavailable): ?>
        <div class="card" role="status"><h2>Catalog temporarily unavailable</h2><p class="muted">Please try again later.</p></div>
    <?php elseif ($products === []): ?>
        <div class="card"><h2>No fabrics to show yet</h2><p class="muted">Our published collections will appear here. Please check back soon.</p></div>
    <?php else: ?>
        <div class="catalog-grid">
            <?php foreach ($products as $product): ?><article class="card catalog-product">
                <span class="badge"><?= e($product['category_name']) ?></span>
                <h2><?= e($product['title']) ?></h2><p class="muted">Code: <?= e($product['product_code']) ?></p>
                <p class="catalog-description"><?= e($product['description']) ?></p>
                <dl class="detail-list">
                    <?php if ($product['composition'] !== null): ?><div><dt>Composition</dt><dd><?= e($product['composition']) ?></dd></div><?php endif; ?>
                    <?php if ($product['width_inch'] !== null): ?><div><dt>Width</dt><dd><?= e($product['width_inch']) ?> in</dd></div><?php endif; ?>
                    <?php if ($product['weight_gsm'] !== null): ?><div><dt>Weight</dt><dd><?= e($product['weight_gsm']) ?> GSM</dd></div><?php endif; ?>
                    <div><dt>Unit</dt><dd><?= e(ucfirst($product['unit'])) ?></dd></div>
                </dl>
            </article><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (! $unavailable): ?><nav class="catalog-pages" aria-label="Catalog pages"><?php if ($page > 1): ?><a href="<?= e(url('/?page=' . ($page - 1))) ?>">Previous</a><?php endif; ?><span>Page <?= e($page) ?></span><?php if ($hasNext): ?><a href="<?= e(url('/?page=' . ($page + 1))) ?>">Next</a><?php endif; ?></nav><?php endif; ?>
</section>
