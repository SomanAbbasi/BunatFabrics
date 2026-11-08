<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $orders */
$orders = $orders ?? [];
?>
<section class="account-page customer-dashboard">
    <header class="page-heading">
        <span class="eyebrow">Customer Account</span>
        <h1>Welcome, <?= e($user['name']) ?></h1>
        <p>Review your account details and manage your wholesale fabric orders.</p>
    </header>

    <div class="account-grid">
        <article class="panel">
            <h2>Account Details</h2>
            <dl class="detail-list">
                <div><dt>Name</dt><dd><?= e($user['name']) ?></dd></div>
                <div><dt>Email</dt><dd><?= e($user['email']) ?></dd></div>
                <div><dt>Member since</dt><dd><?= e(date('M d, Y', strtotime((string) $user['created_at']))) ?></dd></div>
            </dl>
        </article>

        <article class="panel">
            <h2>Start an Order</h2>
            <p class="muted">Browse available fabrics, check specifications, and order required quantities.</p>
            <div class="account-actions">
                <a class="button button-compact" href="<?= e(url('/')) ?>"><?= icon('grid') ?><span>Browse Catalog</span></a>
                <a class="button button-compact button-ghost" href="<?= e(url('/orders')) ?>"><span>All My Orders</span></a>
                <a class="button button-compact button-ghost" href="<?= e(url('/settings')) ?>">Account settings</a>
            </div>
        </article>
    </div>

    <section class="panel" aria-labelledby="recent-orders-title">
        <div class="account-orders-heading">
            <div>
                <h2 id="recent-orders-title">Recent Orders</h2>
                <p class="muted">Track order status and view order details.</p>
            </div>
            <?php if ($orders !== []): ?>
                <a class="button button-compact button-ghost" href="<?= e(url('/orders')) ?>">View all orders (<?= count($orders) ?>)</a>
            <?php endif; ?>
        </div>

        <?php if ($orders === []): ?>
            <div style="text-align: center; padding: 36px 16px; background: var(--surface-soft); border-radius: var(--radius-sm);">
                <p style="margin: 0 0 12px; font-weight: 600; color: var(--ink);">You have no order history yet.</p>
                <p class="muted" style="margin: 0 0 16px; font-size: 0.88rem;">Choose any fabric from the catalog to place your first wholesale order.</p>
                <a class="button button-compact" href="<?= e(url('/')) ?>"><?= icon('grid') ?><span>Browse Fabrics</span></a>
            </div>
        <?php else: ?>
            <div class="orders-desktop-view account-orders-scroll" role="region" aria-labelledby="recent-orders-title" tabindex="0">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--line); text-align: left;">
                            <th scope="col" style="padding: 10px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Order ID</th>
                            <th scope="col" style="padding: 10px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Date</th>
                            <th scope="col" style="padding: 10px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Items / Quantity</th>
                            <th scope="col" style="padding: 10px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Status</th>
                            <th scope="col" style="padding: 10px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); text-align: right;">Total (PKR)</th>
                            <th scope="col" style="padding: 10px 14px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                            <?php
                            $orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
                            $status = (string) ($order['status'] ?? 'pending');
                            $statusClass = 'status-badge-' . $status;
                            ?>
                            <tr style="border-bottom: 1px solid var(--line);">
                                <td style="padding: 14px; font-weight: 700; color: var(--ink);">
                                    <a href="<?= e(url('/orders/show?id=' . $order['id'])) ?>"><?= e($orderNum) ?></a>
                                </td>
                                <td style="padding: 14px; color: var(--muted); font-size: 0.86rem; white-space: nowrap;">
                                    <?= e(date('M d, Y', strtotime((string) $order['created_at']))) ?>
                                </td>
                                <td style="padding: 14px; font-weight: 500;">
                                    <?= e((string) ($order['item_lines'] ?? 1)) ?> lines
                                    <?php if (! empty($order['total_quantity'])): ?>
                                        <span class="text-secondary" style="font-size: 0.82rem; color: var(--muted);">(<?= e(number_format((int) $order['total_quantity'])) ?>m)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px; white-space: nowrap;">
                                    <span class="status-badge <?= e($statusClass) ?>">
                                        <?= e(ucfirst($status)) ?>
                                    </span>
                                </td>
                                <td style="padding: 14px; text-align: right; font-weight: 700; color: var(--ink); white-space: nowrap;">
                                    <?= e(pkr((float) $order['subtotal_pkr'])) ?>
                                </td>
                                <td style="padding: 14px; text-align: center; white-space: nowrap;">
                                    <a class="button button-compact button-ghost" href="<?= e(url('/orders/show?id=' . $order['id'])) ?>" aria-label="<?= e('View order ' . $orderNum) ?>">
                                        View order
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card Layout for Phone Screen Viewports -->
            <div class="orders-mobile-cards" aria-label="Recent orders mobile list">
                <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                    <?php
                    $orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
                    $status = (string) ($order['status'] ?? 'pending');
                    $statusClass = 'status-badge-' . $status;
                    $orderDate = date('M d, Y, h:i A', strtotime((string) $order['created_at']));
                    ?>
                    <article class="order-card-mobile">
                        <header class="order-card-head">
                            <span class="order-id"><?= e($orderNum) ?></span>
                            <span class="status-badge <?= e($statusClass) ?>"><?= e(ucfirst($status)) ?></span>
                        </header>
                        <div class="order-card-body">
                            <div class="order-card-row">
                                <span class="order-label">Placed on</span>
                                <span class="order-val"><?= e($orderDate) ?></span>
                            </div>
                            <div class="order-card-row">
                                <span class="order-label">Volume</span>
                                <span class="order-val">
                                    <strong><?= e((string) ($order['item_lines'] ?? 1)) ?></strong> lines
                                    <?php if (! empty($order['total_quantity'])): ?>
                                        (<?= e(number_format((int) $order['total_quantity'])) ?>m)
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="order-card-row">
                                <span class="order-label">Total Amount</span>
                                <span class="order-val order-total"><?= e(pkr((float) $order['subtotal_pkr'])) ?></span>
                            </div>
                        </div>
                        <footer class="order-card-footer">
                            <a class="button button-compact button-ghost" href="<?= e(url('/orders/show?id=' . $order['id'])) ?>">
                                View Order Details &rarr;
                            </a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</section>
