<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $orders */
?>
<section class="account-page">
    <header class="page-heading">
        <span class="eyebrow">Customer Account</span>
        <h1>My Orders</h1>
        <p>Review past fabric orders, tracking status, and invoice slips.</p>
    </header>

    <?php if ($orders === []): ?>
        <div class="panel text-center" style="padding: 48px 24px; text-align: center;">
            <p class="muted" style="margin-bottom: 20px;">You haven't placed any wholesale fabric orders yet.</p>
            <a class="button button-compact" href="<?= e(url('/')) ?>"><?= icon('grid') ?><span>Browse Fabrics</span></a>
        </div>
    <?php else: ?>
        <div class="panel" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table class="orders-table" style="width: 100%; border-collapse: collapse; font-size: 0.92rem;">
                    <thead>
                        <tr style="background: var(--surface-soft); border-bottom: 2px solid var(--line); text-align: left;">
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Order ID</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Date</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Volume</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Shipping To</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); font-weight: 700;">Status</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); font-weight: 700; text-align: right;">Total (PKR)</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); font-weight: 700; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
                            $status = (string) ($order['status'] ?? 'pending');
                            $statusClass = 'status-badge-' . $status;
                            $orderDate = date('M d, Y, h:i A', strtotime((string) $order['created_at']));
                            $shippingShort = ! empty($order['shipping_address']) ? mb_strimwidth((string) $order['shipping_address'], 0, 32, '...') : '—';
                            ?>
                            <tr style="border-bottom: 1px solid var(--line);">
                                <td style="padding: 12px 16px; font-weight: 700; font-family: monospace;">
                                    <a href="<?= e(url('/orders/show?id=' . $order['id'])) ?>"><?= e($orderNum) ?></a>
                                </td>
                                <td style="padding: 12px 16px; color: var(--muted); white-space: nowrap;">
                                    <?= e($orderDate) ?>
                                </td>
                                <td style="padding: 12px 16px;">
                                    <strong><?= e((string) ($order['item_lines'] ?? 1)) ?></strong> <?= ($order['item_lines'] ?? 1) === 1 ? 'line' : 'lines' ?>
                                    <?php if (! empty($order['total_quantity'])): ?>
                                        <span class="text-secondary small" style="color: var(--muted);">(<?= e(number_format((int) $order['total_quantity'])) ?>m)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px; color: var(--muted);" title="<?= e((string) $order['shipping_address']) ?>">
                                    <?= e($shippingShort) ?>
                                </td>
                                <td style="padding: 12px 16px; white-space: nowrap;">
                                    <span class="status-badge <?= e($statusClass) ?>">
                                        <?= e(ucfirst($status)) ?>
                                    </span>
                                </td>
                                <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: var(--ink); white-space: nowrap;">
                                    <?= e(pkr((float) $order['subtotal_pkr'])) ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: center; white-space: nowrap;">
                                    <a class="button button-compact button-ghost" href="<?= e(url('/orders/show?id=' . $order['id'])) ?>" style="padding: 4px 10px; font-size: 0.82rem;">
                                        View Details &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>
