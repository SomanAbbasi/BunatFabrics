<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;

/** @var array<string, mixed> $product */
/** @var list<array<string, mixed>> $images */
/** @var int $cartQuantity */
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
            <p class="product-code">Code: <?= e($product['product_code']) ?></p>
            <p class="product-price product-price-lg">
                <?= e(pkr($product['price_pkr'])) ?>
                <span style="font-size: 0.95rem; font-weight: normal; color: var(--muted);">/ <?= e($product['unit']) ?></span>
            </p>
            <p class="product-description"><?= nl2br(e($product['description'])) ?></p>

            <dl class="spec-list">
                <?php if ($product['width_inch'] !== null): ?><div><dt>Width</dt><dd><?= e((string) $product['width_inch']) ?> inches</dd></div><?php endif; ?>
                <?php if ($product['weight_gsm'] !== null): ?><div><dt>Weight</dt><dd><?= e((string) $product['weight_gsm']) ?> GSM</dd></div><?php endif; ?>
                <?php if ($product['composition'] !== null && $product['composition'] !== ''): ?><div><dt>Composition</dt><dd><?= e($product['composition']) ?></dd></div><?php endif; ?>
                <div><dt>Sold by</dt><dd><?= e(ucfirst($product['unit'])) ?></dd></div>
                <div><dt>Category</dt><dd><?= e($product['category_name']) ?></dd></div>
            </dl>

            <form action="<?= e(url('/cart/add')) ?>" method="POST" class="add-to-cart" data-cart-form>
                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                <input type="hidden" name="return_to" value="<?= e(url($_SERVER['REQUEST_URI'] ?? '/')) ?>">

                <div class="field">
                    <label for="quantity">Quantity (<?= e($product['unit']) ?>s)</label>
                    <div class="quantity-input-group">
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="1000000" inputmode="numeric" required class="wholesale-qty-input">
                        <span class="unit-tag"><?= e($product['unit']) ?>s</span>
                    </div>
                </div>

                <button type="submit" class="button<?= $cartQuantity > 0 ? ' is-added' : '' ?>" style="margin-top: 14px;">
                    <?= icon($cartQuantity > 0 ? 'check-circle' : 'plus') ?>
                    <span class="btn-text"><?= $cartQuantity > 0 ? 'In cart (' . e((string) $cartQuantity) . ' ' . e($product['unit']) . 's)' : 'Add to cart' ?></span>
                </button>
            </form>

            <?php if ($cartQuantity > 0): ?>
                <p class="cart-inline-note"><?= icon('check-circle') ?> This fabric is in your <a href="<?= e(url('/cart')) ?>">cart</a>. You can add more or proceed to checkout.</p>
            <?php endif; ?>

            <a class="button button-compact button-ghost" href="<?= e(url('/')) ?>"><?= icon('grid') ?><span>Continue browsing</span></a>
        </div>
    </div>
</section>
