<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;

/** @var array<string, mixed> $product */
/** @var list<array<string, mixed>> $images */
?>
<section class="product-detail">
    <a class="back-link" href="<?= e(url('/')) ?>">&larr; Back to catalog</a>

    <div class="product-detail-grid">
        <div class="product-gallery">
            <?php if ($images !== []): ?>
                <?php foreach ($images as $index => $image): ?>
                    <figure class="gallery-image<?= $index === 0 ? ' gallery-image-main' : '' ?>">
                        <img src="<?= e(ProductImageService::url((string) $image['file_path'])) ?>" alt="<?= e($product['title']) ?> photo <?= e((string) ($index + 1)) ?>">
                    </figure>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="gallery-image gallery-image-main">
                    <span class="product-placeholder" aria-hidden="true">B</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-info">
            <span class="badge"><?= e($product['category_name']) ?></span>
            <h1><?= e($product['title']) ?></h1>
            <p class="product-code">Code <?= e($product['product_code']) ?></p>
            <p class="product-price product-price-lg"><?= e(pkr($product['price_pkr'])) ?></p>
            <p class="product-description"><?= nl2br(e($product['description'])) ?></p>

            <dl class="spec-list">
                <?php if ($product['composition'] !== null && $product['composition'] !== ''): ?><div><dt>Composition</dt><dd><?= e($product['composition']) ?></dd></div><?php endif; ?>
                <?php if ($product['width_inch'] !== null): ?><div><dt>Width</dt><dd><?= e($product['width_inch']) ?> inches</dd></div><?php endif; ?>
                <?php if ($product['weight_gsm'] !== null): ?><div><dt>Weight</dt><dd><?= e($product['weight_gsm']) ?> GSM</dd></div><?php endif; ?>
                <div><dt>Sold by</dt><dd><?= e(ucfirst($product['unit'])) ?></dd></div>
                <div><dt>Category</dt><dd><?= e($product['category_name']) ?></dd></div>
            </dl>

            <a class="button button-compact" href="<?= e(url('/')) ?>">Continue browsing</a>
        </div>
    </div>
</section>
