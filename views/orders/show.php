<?php

declare(strict_types=1);

/** @var array<string, mixed> $user */
/** @var array<string, mixed> $order */
/** @var list<array<string, mixed>> $items */

$orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
$status = (string) ($order['status'] ?? 'pending');
$statusClass = 'status-badge-' . $status;
$formattedDate = date('F j, Y, g:i A', strtotime((string) $order['created_at']));
?>
<section class="account-page">
    <div style="margin-bottom: 16px;">
        <a class="back-link" href="<?= e(url('/orders')) ?>" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
            &larr; Back to My Orders
        </a>
    </div>

    <header class="page-heading" style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px;">
        <div>
            <span class="eyebrow">Order Details</span>
            <h1 style="margin: 4px 0 6px;">Order <?= e($orderNum) ?></h1>
            <p class="muted">Placed on <?= e($formattedDate) ?></p>
        </div>
        <div style="text-align: right;">
            <div style="margin-bottom: 8px;">
                <span class="status-badge <?= e($statusClass) ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                    <?= e(ucfirst($status)) ?>
                </span>
            </div>
            <div style="font-size: 1.35rem; font-weight: 800; color: var(--ink);">
                <?= e(pkr((float) $order['subtotal_pkr'])) ?>
            </div>
        </div>
    </header>

    <?php if ($status === 'dispatched' || ! empty($order['tracking_number'])): ?>
        <div class="panel" style="background: var(--surface-soft); border-left: 4px solid #7c3aed; margin-bottom: 24px; padding: 16px 20px;">
            <h2 style="margin: 0 0 4px; font-size: 1rem; color: var(--ink);">Order Dispatched</h2>
            <p style="margin: 0; font-size: 0.9rem; color: var(--muted);">
                <?php if (! empty($order['tracking_number'])): ?>
                    Tracking / Bilty Number: <strong style="color: var(--ink);"><?= e($order['tracking_number']) ?></strong> &middot;
                <?php endif; ?>
                Your order is dispatched for delivery.
            </p>
            <?php if (! empty($order['admin_notes'])): ?>
                <div style="margin-top: 10px; font-size: 0.86rem; color: var(--muted);">
                    <strong>Notes:</strong> <?= e($order['admin_notes']) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($status === 'delivered'): ?>
        <div class="alert alert-success" style="margin-bottom: 24px;">
            <?= icon('check-circle', 'icon alert-icon') ?>
            <span>This order has been delivered. Thank you for ordering from Bunat Fabrics.</span>
        </div>
    <?php elseif ($status === 'rejected' || $status === 'cancelled'): ?>
        <div class="alert alert-error" style="margin-bottom: 24px;">
            <?= icon('alert-circle', 'icon alert-icon') ?>
            <span>This order was cancelled or rejected. Contact support for any queries.</span>
        </div>
    <?php endif; ?>

    <div class="cart-layout order-detail-layout">
        <div class="panel" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 20px; border-bottom: 1px solid var(--line); font-weight: 700; font-size: 1rem;">
                Items in Order (<?= count($items) ?> <?= count($items) === 1 ? 'fabric' : 'fabrics' ?>)
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem;">
                    <thead>
                        <tr style="background: var(--surface-soft); border-bottom: 1px solid var(--line); text-align: left;">
                            <th style="padding: 12px 16px; width: 60px;"></th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Fabric</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted);">Code</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); text-align: right;">Rate</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); text-align: center;">Qty</th>
                            <th style="padding: 12px 16px; font-size: 0.78rem; text-transform: uppercase; color: var(--muted); text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr style="border-bottom: 1px solid var(--line);">
                                <td style="padding: 10px 16px;">
                                    <?php if (! empty($item['image_path'])): ?>
                                        <img src="<?= e(\App\Admin\Products\ProductImageService::url((string) $item['image_path'])) ?>" alt="<?= e($item['title']) ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--line);">
                                    <?php else: ?>
                                        <div style="width: 48px; height: 48px; border-radius: 6px; background: var(--surface-soft); display: grid; place-items: center; font-weight: 700; color: var(--muted); border: 1px solid var(--line);">B</div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px 16px; font-weight: 600;">
                                    <a href="<?= e(url('/product?id=' . $item['product_id'])) ?>">
                                        <?= e($item['title']) ?>
                                    </a>
                                </td>
                                <td style="padding: 12px 16px; font-family: monospace; color: var(--muted);">
                                    <?= e($item['product_code']) ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: right; color: var(--muted);">
                                    <?= e(pkr((float) $item['unit_price_pkr'])) ?> / <?= e($item['unit']) ?>
                                </td>
                                <td style="padding: 12px 16px; text-align: center; font-weight: 600;">
                                    <?= e(number_format((int) $item['quantity'])) ?> <?= e($item['unit']) ?>s
                                </td>
                                <td style="padding: 12px 16px; text-align: right; font-weight: 700; color: var(--ink);">
                                    <?= e(pkr((float) $item['line_total_pkr'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: var(--surface-soft); font-weight: 800; font-size: 1rem;">
                            <td colspan="5" style="padding: 14px 16px; text-align: right;">Subtotal:</td>
                            <td style="padding: 14px 16px; text-align: right; color: var(--ink);">
                                <?= e(pkr((float) $order['subtotal_pkr'])) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <aside style="display: flex; flex-direction: column; gap: 20px;">
            <div class="panel">
                <h2 style="font-size: 1rem; font-weight: 700; margin: 0 0 12px;">Delivery Information</h2>
                <dl class="detail-list" style="margin: 0;">
                    <div style="margin-bottom: 10px;">
                        <dt style="font-size: 0.76rem; text-transform: uppercase; color: var(--muted);">Recipient</dt>
                        <dd style="margin: 2px 0 0; font-weight: 600;"><?= e($user['name']) ?></dd>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <dt style="font-size: 0.76rem; text-transform: uppercase; color: var(--muted);">Phone</dt>
                        <dd style="margin: 2px 0 0; font-weight: 600;"><?= e($order['contact_phone'] ?: 'N/A') ?></dd>
                    </div>
                    <div style="margin-bottom: 10px;">
                        <dt style="font-size: 0.76rem; text-transform: uppercase; color: var(--muted);">Address</dt>
                        <dd style="margin: 2px 0 0; line-height: 1.4;"><?= nl2br(e($order['shipping_address'] ?: 'N/A')) ?></dd>
                    </div>
                </dl>
            </div>

            <div class="panel" style="background: var(--surface-soft);">
                <a class="button button-compact button-ghost w-100" href="<?= e(url('/')) ?>" style="justify-content: center;">
                    <?= icon('grid') ?><span>Browse More Fabrics</span>
                </a>
            </div>
        </aside>
    </div>
</section>
