<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;

/** @var list<array{id: int, name: string, slug: string, products: list<array<string, mixed>>}> $groups */
?>
<section class="storefront">
    <header class="store-hero">
        <span class="eyebrow">Wholesale fabrics</span>
        <h1>Fabric collections for your business</h1>
        <p>Browse by category, compare specifications and PKR pricing, then open any fabric for the full details.</p>
    </header>

    <?php if ($unavailable): ?>
        <div class="panel" role="status">
            <h2>Catalog temporarily unavailable</h2>
            <p class="muted">Please try again in a moment.</p>
        </div>
    <?php elseif ($groups === []): ?>
        <div class="panel">
            <h2>No fabrics published yet</h2>
            <p class="muted">Published fabric collections will appear here.</p>
        </div>
    <?php else: ?>
        <nav class="category-nav" aria-label="Fabric categories">
            <?php foreach ($groups as $group): ?>
                <a href="#category-<?= e((string) $group['id']) ?>">
                    <?= e($group['name']) ?>
                    <span><?= e((string) count($group['products'])) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php foreach ($groups as $group): ?>
            <section class="category-section" id="category-<?= e((string) $group['id']) ?>" aria-labelledby="category-<?= e((string) $group['id']) ?>-title">
                <header class="section-heading">
                    <h2 id="category-<?= e((string) $group['id']) ?>-title"><?= e($group['name']) ?></h2>
                    <span><?= e((string) count($group['products'])) ?> <?= count($group['products']) === 1 ? 'fabric' : 'fabrics' ?></span>
                </header>
                <div class="product-grid">
                    <?php foreach ($group['products'] as $product): ?>
                        <a class="product-card" href="<?= e(url('/product?id=' . $product['id'])) ?>">
                            <div class="product-media">
                                <?php if (! empty($product['image_path'])): ?>
                                    <img src="<?= e(ProductImageService::url((string) $product['image_path'])) ?>" alt="<?= e($product['title']) ?>" loading="lazy">
                                <?php else: ?>
                                    <span class="product-placeholder" aria-hidden="true">B</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-body">
                                <span class="product-code"><?= e($product['product_code']) ?></span>
                                <h3><?= e($product['title']) ?></h3>
                                <p class="product-price"><?= e(pkr($product['price_pkr'])) ?></p>
                                <dl class="product-meta">
                                    <?php if ($product['composition'] !== null && $product['composition'] !== ''): ?><div><dt>Composition</dt><dd><?= e($product['composition']) ?></dd></div><?php endif; ?>
                                    <?php if ($product['width_inch'] !== null): ?><div><dt>Width</dt><dd><?= e($product['width_inch']) ?> in</dd></div><?php endif; ?>
                                    <?php if ($product['weight_gsm'] !== null): ?><div><dt>Weight</dt><dd><?= e($product['weight_gsm']) ?> GSM</dd></div><?php endif; ?>
                                </dl>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
