<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array<string, mixed> $order */
$orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
?>
<section class="cart-page">
    <header class="page-heading">
        <span class="eyebrow">Order Placed</span>
        <h1>Thank you, <?= e($user['name']) ?></h1>
        <p>Your wholesale fabric order has been successfully placed with Bunat Fabrics.</p>
    </header>

    <div class="panel order-confirmation" style="text-align: center; padding: 40px 24px;">
        <span class="confirmation-icon" style="display: grid; place-items: center; width: 64px; height: 64px; margin: 0 auto 16px; background: var(--success-soft); color: var(--success); border-radius: 50%;">
            <?= icon('check-circle', 'icon') ?>
        </span>
        <h2 style="font-size: 1.5rem; margin-bottom: 8px;">Order <?= e($orderNum) ?> is Pending Confirmation</h2>
        <p class="muted" style="max-width: 500px; margin: 0 auto 16px;">
            Our textile desk will contact you on <strong><?= e($order['contact_phone']) ?></strong> to confirm bolt quantities and dispatch schedules.
        </p>
        <p style="font-size: 1.15rem; margin-bottom: 24px;">Order Subtotal: <strong><?= e(pkr((float) $order['subtotal_pkr'])) ?></strong></p>

        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 12px;">
            <a class="button button-primary" href="<?= e(url('/orders/show?id=' . $order['id'])) ?>">
                <?= icon('file-text') ?><span>Track in My Orders</span>
            </a>
            <a class="button button-ghost" href="<?= e(url('/')) ?>">
                <?= icon('grid') ?><span>Browse More Fabrics</span>
            </a>
        </div>
    </div>
</section>
