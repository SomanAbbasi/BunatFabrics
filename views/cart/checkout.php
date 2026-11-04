<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;

/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $items */
/** @var float $subtotal */
/** @var array<string, int> $unitTotals */
/** @var int $itemCount */
/** @var array<string, string> $errors */
?>
<section class="cart-page">
    <header class="page-heading">
        <span class="eyebrow">Checkout</span>
        <h1>Review your order</h1>
        <p>Confirm the fabrics and add your delivery contact before placing the order.</p>
    </header>

    <form action="<?= e(url('/checkout')) ?>" method="POST" class="checkout-layout">
        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">

        <div class="checkout-main">
            <article class="panel">
                <h2><?= icon('phone') ?> Contact details</h2>

                <div class="field">
                    <label for="phone">Phone number</label>
                    <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="+92 300 1234567" autocomplete="tel" inputmode="tel" pattern="\+?[0-9][0-9\s().-]{6,19}" required>
                    <span class="field-hint">We will use this number to confirm your order.</span>
                    <?php if (isset($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label for="address">Delivery address</label>
                    <textarea id="address" name="address" rows="4" placeholder="Street, area, city, postal code" autocomplete="street-address" required><?= e(old('address')) ?></textarea>
                    <span class="field-hint">Include the full address where the fabrics should be delivered.</span>
                    <?php if (isset($errors['address'])): ?><span class="field-error"><?= e($errors['address']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="city">City</label>
                    <input id="city" name="city" maxlength="80" autocomplete="address-level2" required value="<?= e(old('city')) ?>">
                    <?php if (isset($errors['city'])): ?><span class="field-error"><?= e($errors['city']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="payment_method">Payment method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Choose a payment method</option>
                        <option value="bank" <?= old('payment_method') === 'bank' ? 'selected' : '' ?>>Bank transfer</option>
                        <option value="cod" <?= old('payment_method') === 'cod' ? 'selected' : '' ?>>Cash on delivery</option>
                    </select>
                    <?php if (isset($errors['payment_method'])): ?><span class="field-error"><?= e($errors['payment_method']) ?></span><?php endif; ?>
                </div>
            </article>

            <article class="panel">
                <h2><?= icon('user') ?> Account</h2>
                <dl class="detail-list">
                    <div><dt>Name</dt><dd><?= e($user['name']) ?></dd></div>
                    <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
                </dl>
            </article>

            <article class="panel">
                <h2><?= icon('package') ?> Order items</h2>
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

            <button type="submit" class="button"><?= icon('lock') ?><span>Place order</span></button>

            <a class="back-link" href="<?= e(url('/cart')) ?>"><?= icon('cart') ?><span>Back to cart</span></a>
        </aside>
    </form>
</section>
