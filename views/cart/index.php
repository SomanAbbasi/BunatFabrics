<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;

/** @var list<array<string, mixed>> $items */
/** @var float $subtotal */
/** @var array<string, int> $unitTotals */
/** @var int $itemCount */
?>
<section class="cart-page">
    <header class="page-heading">
        <span class="eyebrow">Wholesale order</span>
        <h1>Your cart</h1>
        <p>Review quantities, remove fabrics, and continue to checkout when your order is ready.</p>
    </header>

    <?php if ($items === []): ?>
        <div class="panel empty-cart">
            <p class="empty-note-title">Your cart is empty</p>
            <p class="muted">Add fabrics from the catalog to start a wholesale order.</p>
            <a class="button button-compact" href="<?= e(url('/')) ?>">Browse fabrics</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-lines">
                <?php foreach ($items as $item): ?>
                    <article class="cart-line">
                        <a class="cart-line-media" href="<?= e(url('/product?id=' . $item['id'])) ?>">
                            <?php if (! empty($item['image_path'])): ?>
                                <img src="<?= e(ProductImageService::url((string) $item['image_path'])) ?>" alt="<?= e($item['title']) ?>">
                            <?php else: ?>
                                <span class="product-placeholder" aria-hidden="true">B</span>
                            <?php endif; ?>
                        </a>

                        <div class="cart-line-body">
                            <span class="product-code"><?= e($item['product_code']) ?></span>
                            <h2><a href="<?= e(url('/product?id=' . $item['id'])) ?>"><?= e($item['title']) ?></a></h2>
                            <p class="muted"><?= e(pkr($item['price_pkr'])) ?> per <?= e($item['unit']) ?></p>
                        </div>

                        <div class="cart-line-actions">
                            <form action="<?= e(url('/cart/update')) ?>" method="POST" class="cart-qty-form">
                                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                                <input type="hidden" name="product_id" value="<?= e((string) $item['id']) ?>">
                                <label for="quantity-<?= e((string) $item['id']) ?>">Qty</label>
                                <input type="number" id="quantity-<?= e((string) $item['id']) ?>" name="quantity" value="<?= e((string) $item['quantity']) ?>" min="1" max="999" inputmode="numeric">
                                <button type="submit" class="button button-compact button-ghost">Update</button>
                            </form>

                            <form action="<?= e(url('/cart/remove')) ?>" method="POST" class="cart-remove-form">
                                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                                <input type="hidden" name="product_id" value="<?= e((string) $item['id']) ?>">
                                <button type="submit" class="link-button danger-link">Remove</button>
                            </form>
                        </div>

                        <div class="cart-line-total">
                            <span class="muted">Line total</span>
                            <strong><?= e(pkr($item['line_total'])) ?></strong>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <aside class="panel cart-summary">
                <h2>Order summary</h2>

                <dl class="summary-list">
                    <div>
                        <dt>Items</dt>
                        <dd><?= e((string) $itemCount) ?></dd>
                    </div>
                    <?php foreach ($unitTotals as $unit => $quantity): ?>
                        <div>
                            <dt>Total <?= e($unit) ?></dt>
                            <dd><?= e((string) $quantity) ?> <?= e($unit . ($quantity === 1 ? '' : 's')) ?></dd>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <dt>Subtotal</dt>
                        <dd><?= e(pkr($subtotal)) ?></dd>
                    </div>
                </dl>

                <a class="button" href="<?= e(url('/checkout')) ?>">Proceed to checkout</a>
                <a class="back-link" href="<?= e(url('/')) ?>">Continue browsing</a>
            </aside>
        </div>
    <?php endif; ?>
</section>
