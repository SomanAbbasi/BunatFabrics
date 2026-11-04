<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;

/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $items */
/** @var float $subtotal */
/** @var array<string, int> $unitTotals */
/** @var int $itemCount */
?>
<section class="cart-page">
    <header class="page-heading">
        <span class="eyebrow">Checkout</span>
        <h1>Review your order</h1>
        <p>Confirm the fabrics and quantities below, then place your wholesale order.</p>
    </header>

    <div class="checkout-layout">
        <div class="checkout-main">
            <article class="panel">
                <h2>Account</h2>
                <dl class="detail-list">
                    <div><dt>Name</dt><dd><?= e($user['name']) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
                </dl>
            </article>

            <article class="panel">
                <h2>Order items</h2>
                <div class="checkout-lines">
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-line">
                            <div class="checkout-line-media">
                                <?php if (! empty($item['image_path'])): ?>
                                    <img src="<?= e(ProductImageService::url((string) $item['image_path'])) ?>" alt="<?= e($item['title']) ?>">
                                <?php else: ?>
                                    <span class="product-placeholder" aria-hidden="true">B</span>
                                <?php endif; ?>
                            </div>
                            <div class="checkout-line-body">
                                <strong><?= e($item['title']) ?></strong>
                                <span class="muted"><?= e($item['product_code']) ?> &middot; <?= e((string) $item['quantity']) ?> <?= e($item['unit'] . ((int) $item['quantity'] === 1 ? '' : 's')) ?> &times; <?= e(pkr($item['price_pkr'])) ?></span>
                            </div>
                            <strong class="checkout-line-total"><?= e(pkr($item['line_total'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
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
                    <dt>Total</dt>
                    <dd><?= e(pkr($subtotal)) ?></dd>
                </div>
            </dl>

            <form action="<?= e(url('/checkout')) ?>" method="POST">
                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                <button type="submit" class="button">Place order</button>
            </form>

            <a class="back-link" href="<?= e(url('/cart')) ?>">Back to cart</a>
        </aside>
    </div>
</section>
