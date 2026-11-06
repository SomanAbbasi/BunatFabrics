<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array<string, mixed> $order */
/** @var list<array<string, mixed>> $items */

$orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
$status = (string) ($order['status'] ?? 'pending');
$statusClass = 'status-badge-' . $status;
?>
<section class="account-page">
    <div style="margin-bottom: 20px;">
        <a class="back-link" href="<?= e(url('/orders')) ?>" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
            &larr; Back to My Orders
        </a>
    </div>

    <header class="page-heading" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px;">
        <div>
            <span class="eyebrow">Wholesale Order Details</span>
            <h1 style="margin: 4px 0 6px;">Order <?= e($orderNum) ?></h1>
            <p class="muted">Placed on <?= e(date('F j, Y &middot; g:i A', strtotime((string) $order['created_at']))) ?></p>
        </div>
        <div style="text-align: right;">
            <div style="margin-bottom: 8px;">
                <span class="status-badge <?= e($statusClass) ?>" style="font-size: 0.92rem; padding: 6px 16px;">
                    <?= e(ucfirst($status)) ?>
                </span>
            </div>
            <div style="font-size: 1.4rem; font-weight: 800; color: var(--ink);">
                <?= e(pkr((float) $order['subtotal_pkr'])) ?>
            </div>
        </div>
    </header>

    <?php if ($status === 'dispatched' || ! empty($order['tracking_number'])): ?>
        <div class="panel" style="background: #f5f3ff; border-color: #ddd6fe; margin-bottom: 24px; padding: 18px 24px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: #ede9fe; color: #6d28d9; display: grid; place-items: center;">
                    <?= icon('check-circle') ?>
                </div>
                <div>
                    <h2 style="margin: 0 0 2px; font-size: 1rem; color: #4c1d95;">Order Dispatched / Shipped</h2>
                    <p style="margin: 0; font-size: 0.88rem; color: #5b21b6;">
                        <?php if (! empty($order['tracking_number'])): ?>
                            Tracking Number: <strong><?= e($order['tracking_number']) ?></strong> &middot;
                        <?php endif; ?>
                        Your shipment is on its way to your manufacturing facility.
                    </p>
                </div>
            </div>
            <?php if (! empty($order['admin_notes'])): ?>
                <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e9d5ff; font-size: 0.86rem; color: #581c87;">
                    <strong>Dispatch Notes:</strong> <?= e($order['admin_notes']) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($status === 'delivered'): ?>
        <div class="alert alert-success" style="margin-bottom: 24px;">
            <?= icon('check-circle', 'icon alert-icon') ?>
            <span>This order has been marked as <strong>Delivered</strong>. Thank you for partnering with Bunat Fabrics!</span>
        </div>
    <?php elseif ($status === 'rejected' || $status === 'cancelled'): ?>
        <div class="alert alert-error" style="margin-bottom: 24px;">
            <?= icon('alert-circle', 'icon alert-icon') ?>
            <span>This order has been cancelled or rejected. Please contact our support desk for inquiries.</span>
        </div>
    <?php endif; ?>

    <div class="cart-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
        <div class="panel" style="padding: 0; overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid var(--line); font-weight: 700; font-size: 1.05rem;">
                Ordered Fabrics (<?= count($items) ?> <?= count($items) === 1 ? 'line' : 'lines' ?>)
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem;">
                    <thead>
                        <tr style="background: var(--surface-soft); border-bottom: 1px solid var(--line); text-align: left;">
                            <th style="padding: 12px 18px; font-size: 0.8rem; text-transform: uppercase; color: var(--muted);">Fabric</th>
                            <th style="padding: 12px 18px; font-size: 0.8rem; text-transform: uppercase; color: var(--muted);">Code</th>
                            <th style="padding: 12px 18px; font-size: 0.8rem; text-transform: uppercase; color: var(--muted); text-align: right;">Rate / Unit</th>
                            <th style="padding: 12px 18px; font-size: 0.8rem; text-transform: uppercase; color: var(--muted); text-align: center;">Wholesale Qty</th>
                            <th style="padding: 12px 18px; font-size: 0.8rem; text-transform: uppercase; color: var(--muted); text-align: right;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom: 1px solid var(--line);">
                                <td style="padding: 14px 18px; font-weight: 600;">
                                    <a href="<?= e(url('/product?id=' . $item['product_id'])) ?>">
                                        <?= e($item['title']) ?>
                                    </a>
                                </td>
                                <td style="padding: 14px 18px; font-family: monospace; color: var(--muted);">
                                    <?= e($item['product_code']) ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: right; color: var(--muted);">
                                    <?= e(pkr((float) $item['unit_price_pkr'])) ?> / <?= e($item['unit']) ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: center; font-weight: 700;">
                                    <?= e(number_format((int) $item['quantity'])) ?> <?= e($item['unit']) ?>s
                                </td>
                                <td style="padding: 14px 18px; text-align: right; font-weight: 800; color: var(--ink);">
                                    <?= e(pkr((float) $item['line_total_pkr'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--surface-soft); font-weight: 800; font-size: 1.05rem;">
                            <td colspan="4" style="padding: 16px 18px; text-align: right;">Order Subtotal:</td>
                            <td style="padding: 16px 18px; text-align: right; color: var(--ink);">
                                <?= e(pkr((float) $order['subtotal_pkr'])) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <aside style="display: flex; flex-direction: column; gap: 20px;">
            <div class="panel">
                <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0 0 14px;">Delivery & Contact Details</h2>
                <dl class="detail-list" style="margin: 0;">
                    <div style="margin-bottom: 12px;">
                        <dt style="font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Client Name</dt>
                        <dd style="margin: 2px 0 0; font-weight: 600;"><?= e($user['name']) ?></dd>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <dt style="font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Contact Phone</dt>
                        <dd style="margin: 2px 0 0; font-weight: 600;"><?= e($order['contact_phone']) ?></dd>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <dt style="font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Shipping Destination</dt>
                        <dd style="margin: 2px 0 0; line-height: 1.4;"><?= nl2br(e($order['shipping_address'])) ?></dd>
                    </div>
                </dl>
            </div>

            <div class="panel" style="background: var(--surface-soft);">
                <h2 style="font-size: 1.05rem; font-weight: 700; margin: 0 0 8px;">Wholesale Support</h2>
                <p class="muted" style="font-size: 0.88rem; margin-bottom: 16px;">
                    Have questions about this bundle, roll packing, or custom dyeing? Contact our mill operations team.
                </p>
                <a class="button button-ghost button-compact w-100" href="<?= e(url('/')) ?>" style="justify-content: center;">
                    <?= icon('grid') ?><span>Browse More Fabrics</span>
                </a>
            </div>
        </aside>
    </div>
</section>
