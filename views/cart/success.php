<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array<string, mixed> $order */
/** @var list<array<string, mixed>> $items */
?>
<section class="cart-page">
    <header class="page-heading">
        <span class="eyebrow">Order placed</span>
        <h1>Thank you, <?= e($user['name']) ?></h1>
        <p>Your wholesale order has been received and is awaiting confirmation.</p>
    </header>

    <div class="checkout-layout">
        <div class="checkout-main">
            <article class="panel">
                <h2>Order #<?= e((string) $order['id']) ?></h2>
                <dl class="detail-list">
                    <div><dt>Status</dt><dd><?= e(ucfirst((string) $order['status'])) ?></dd></div>
                    <div><dt>Placed</dt><dd><?= e((string) $order['created_at']) ?></dd></div>
                    <div><dt>Total</dt><dd><?= e(pkr($order['subtotal_pkr'])) ?></dd></div>
                </dl>
            </article>

            <article class="panel">
                <h2>Items</h2>
                <div class="checkout-lines">
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-line">
                            <div class="checkout-line-body">
                                <strong><?= e($item['title']) ?></strong>
                                <span class="muted"><?= e($item['product_code']) ?> &middot; <?= e((string) $item['quantity']) ?> <?= e($item['unit'] . ((int) $item['quantity'] === 1 ? '' : 's')) ?> &times; <?= e(pkr($item['unit_price_pkr'])) ?></span>
                            </div>
                            <strong class="checkout-line-total"><?= e(pkr($item['line_total_pkr'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>

        <aside class="panel cart-summary">
            <h2>What happens next?</h2>
            <p class="muted">Our team will review stock and confirm your order. Keep this order number for reference.</p>
            <a class="button" href="<?= e(url('/')) ?>">Browse more fabrics</a>
            <a class="back-link" href="<?= e(url('/dashboard')) ?>">Go to dashboard</a>
        </aside>
    </div>
</section>
