<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array<string, mixed> $order */
$orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
?>
<section class="success-page">
    <div class="success-card panel">
        <span class="confirmation-icon" aria-hidden="true">
            <?= icon('check-circle', 'icon') ?>
        </span>

        <span class="eyebrow">Order Placed</span>
        <h1 class="success-title">Thank you, <?= e($user['name']) ?></h1>
        <p class="success-subtitle">Your wholesale fabric order has been successfully placed with Bunat Fabrics.</p>

        <div class="success-summary-box">
            <div class="success-summary-head">
                <span class="success-order-num">Order <?= e($orderNum) ?></span>
                <span class="status-badge status-badge-pending">Pending Confirmation</span>
            </div>

            <p class="success-contact-text">
                Our textile desk will contact you on <strong><?= e($order['contact_phone']) ?></strong> to confirm bolt quantities and dispatch schedules.
            </p>

            <div class="success-total-row">
                <span class="muted">Order Subtotal:</span>
                <strong class="success-total-val"><?= e(pkr((float) $order['subtotal_pkr'])) ?></strong>
            </div>
        </div>

        <div class="success-actions">
            <a class="button button-primary" href="<?= e(url('/orders/show?id=' . $order['id'])) ?>">
                <?= icon('package') ?><span>Track in My Orders</span>
            </a>
            <a class="button button-ghost" href="<?= e(url('/')) ?>">
                <?= icon('grid') ?><span>Browse More Fabrics</span>
            </a>
        </div>
    </div>
</section>
