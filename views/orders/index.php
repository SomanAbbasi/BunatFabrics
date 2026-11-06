<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $orders */
?>
<section class="account-page">
    <header class="page-heading">
        <span class="eyebrow">Customer Account</span>
        <h1>My Wholesale Orders</h1>
        <p>Track your fabric orders, review quantities, check dispatch status, and view invoices.</p>
    </header>

    <?php if ($orders === []): ?>
        <div class="panel empty-orders text-center" style="padding: 48px 24px; text-align: center;">
            <div style="width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 50%; background: var(--surface-soft); display: grid; place-items: center; color: var(--muted);">
                <?= icon('cart', 'icon') ?>
            </div>
            <h2>No orders placed yet</h2>
            <p class="muted" style="margin-bottom: 24px;">You haven't placed any wholesale orders with Bunat Fabrics yet.</p>
            <a class="button button-compact" href="<?= e(url('/')) ?>"><?= icon('grid') ?><span>Browse Fabric Collections</span></a>
        </div>
    <?php else: ?>
        <div class="orders-list" style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($orders as $order): ?>
                <?php
                $orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
                $status = (string) ($order['status'] ?? 'pending');
                $statusClass = 'status-badge-' . $status;
                ?>
                <article class="panel order-card" style="padding: 20px 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)) auto; gap: 20px; align-items: center;">
                    <div>
                        <span class="text-secondary small d-block" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted);">Order ID</span>
                        <strong style="font-size: 1.15rem; font-weight: 800;"><?= e($orderNum) ?></strong>
                        <div style="font-size: 0.84rem; color: var(--muted); margin-top: 4px;">
                            Placed on <?= e(date('M d, Y &middot; h:i A', strtotime((string) $order['created_at']))) ?>
                        </div>
                    </div>

                    <div>
                        <span class="text-secondary small d-block" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted);">Wholesale Quantity</span>
                        <div style="font-size: 0.96rem; font-weight: 600; color: var(--ink);">
                            <?= e((string) ($order['item_lines'] ?? 1)) ?> <?= ($order['item_lines'] ?? 1) === 1 ? 'item' : 'items' ?>
                            <?php if (! empty($order['total_quantity'])): ?>
                                &middot; <?= e(number_format((int) $order['total_quantity'])) ?> meters/yards
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.84rem; color: var(--muted); margin-top: 4px;">
                            Ship to: <?= e(mb_strimwidth((string) $order['shipping_address'], 0, 32, '...')) ?>
                        </div>
                    </div>

                    <div>
                        <span class="text-secondary small d-block" style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--muted);">Status & Total</span>
                        <div style="margin-bottom: 6px;">
                            <span class="status-badge <?= e($statusClass) ?>">
                                <?= e(ucfirst($status)) ?>
                            </span>
                        </div>
                        <strong style="font-size: 1.15rem; font-weight: 800; color: var(--ink);"><?= e(pkr((float) $order['subtotal_pkr'])) ?></strong>
                    </div>

                    <div style="justify-self: end;">
                        <a class="button button-compact" href="<?= e(url('/orders/show?id=' . $order['id'])) ?>">
                            <span>View Details</span>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
