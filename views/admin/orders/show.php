<?php

declare(strict_types=1);

use App\Admin\Products\ProductImageService;
use App\Core\Session;
use App\Orders\OrderService;

/** @var array<string, mixed> $user */
/** @var array<string, mixed> $order */
/** @var list<array<string, mixed>> $items */

$orderNum = ! empty($order['order_number']) ? $order['order_number'] : sprintf('BNT-%05d', $order['id']);
$status = (string) ($order['status'] ?? 'pending');
$badgeClass = match ($status) {
    'pending'    => 'bg-warning-lt text-warning-emphasis',
    'confirmed', 'processing' => 'bg-azure-lt',
    'dispatched' => 'bg-purple-lt text-purple',
    'delivered'  => 'bg-success-lt text-success',
    'rejected', 'cancelled' => 'bg-danger-lt text-danger',
    default      => 'bg-secondary-lt',
};
$totalMeters = array_sum(array_column($items, 'quantity'));
?>
<div class="mb-3">
    <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-outline-secondary btn-sm">
        &larr; Back to Orders List
    </a>
</div>

<div class="row g-4">
    <!-- Left Column: Order Breakdown & Client Details -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="card-title mb-0">Wholesale Invoice Slip</h3>
                    <div class="text-secondary small">Unique ID: <strong class="font-monospace text-body"><?= e($orderNum) ?></strong></div>
                </div>
                <span class="badge <?= e($badgeClass) ?> text-uppercase py-2 px-3">
                    <?= e(OrderService::ALL_STATUSES[$status] ?? ucfirst($status)) ?>
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th style="width: 64px;">Fabric</th>
                            <th>Description</th>
                            <th>Code</th>
                            <th class="text-end">Wholesale Rate</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Line Total (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php if (! empty($item['image_path'])): ?>
                                        <img src="<?= e(ProductImageService::url((string) $item['image_path'])) ?>" alt="<?= e($item['title']) ?>" class="rounded" style="width: 52px; height: 52px; object-fit: cover; border: 1px solid var(--tblr-border-color, #e6e8ec);">
                                    <?php else: ?>
                                        <div class="rounded bg-light text-secondary d-flex align-items-center justify-content-center fw-bold" style="width: 52px; height: 52px; border: 1px solid var(--tblr-border-color, #e6e8ec);">
                                            B
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= e($item['title']) ?></div>
                                    <div class="text-secondary small">Unit: <?= e(ucfirst($item['unit'])) ?></div>
                                </td>
                                <td class="font-monospace text-secondary small">
                                    <?= e($item['product_code']) ?>
                                </td>
                                <td class="text-end text-secondary">
                                    <?= e(pkr((float) $item['unit_price_pkr'])) ?>
                                </td>
                                <td class="text-center fw-bold">
                                    <?= e(number_format((int) $item['quantity'])) ?> <?= e($item['unit']) ?>s
                                </td>
                                <td class="text-end fw-bold">
                                    <?= e(pkr((float) $item['line_total_pkr'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold text-secondary">Total Wholesale Volume:</td>
                            <td class="text-center fw-bold"><?= e(number_format($totalMeters)) ?> units</td>
                            <td></td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="5" class="text-end h4 mb-0 fw-bold">Grand Subtotal:</td>
                            <td class="text-end h3 mb-0 fw-bold text-primary"><?= e(pkr((float) $order['subtotal_pkr'])) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Customer & Delivery Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Customer &amp; Shipping Destination</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-secondary small">Customer Name</div>
                        <div class="fw-bold fs-3"><?= e($order['customer_name'] ?? 'N/A') ?></div>
                        <div class="text-secondary"><?= e($order['customer_email'] ?? '') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-secondary small">Contact Phone</div>
                        <div class="fw-bold fs-3"><?= e($order['contact_phone'] ?: 'None provided') ?></div>
                    </div>
                    <div class="col-12 pt-2 border-top">
                        <div class="text-secondary small">Delivery Address (Apparel Mill / Warehouse)</div>
                        <div class="p-3 mt-1 bg-light-subtle rounded border"><?= nl2br(e($order['shipping_address'] ?: 'None specified')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Status & Fulfillment Action Card -->
    <div class="col-lg-4">
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h3 class="card-title text-white">Order Status &amp; Fulfillment</h3>
            </div>
            <div class="card-body">
                <form action="<?= e(url('/admin/orders/status')) ?>" method="POST">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <input type="hidden" name="id" value="<?= e((string) $order['id']) ?>">
                    <input type="hidden" name="return_to" value="<?= e(url('/admin/orders/show?id=' . $order['id'])) ?>">

                    <div class="mb-3">
                        <label class="form-label required">Order Status</label>
                        <select name="status" class="form-select" required>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                            <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Confirmed / In Preparation</option>
                            <option value="processing" <?= $status === 'processing' ? 'selected' : '' ?>>Processing / Bolt Cutting</option>
                            <option value="dispatched" <?= $status === 'dispatched' ? 'selected' : '' ?>>Dispatched / En Route</option>
                            <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected / Stock Unavailable</option>
                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <div class="form-text">Choose status to update customer records immediately.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dispatch / Tracking Number</label>
                        <input type="text" name="tracking_number" class="form-control" value="<?= e((string) ($order['tracking_number'] ?? '')) ?>" placeholder="e.g. TCS #781923, Cargo Roll #55">
                        <div class="form-text">Tracking details are displayed to the customer in their portal.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Admin Notes / Dispatch Details</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Notes on fabric roll packing, freight transporter, or fabric batches..."><?= e((string) ($order['admin_notes'] ?? '')) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Update Order Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Meta Details Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Audit Information</h3>
            </div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt class="text-secondary small">Unique Order Code</dt>
                    <dd class="fw-bold font-monospace"><?= e($orderNum) ?></dd>

                    <dt class="text-secondary small">Internal DB ID</dt>
                    <dd>#<?= e((string) $order['id']) ?></dd>

                    <dt class="text-secondary small">Order Placed At</dt>
                    <dd><?= e(date('F j, Y, g:i:s A', strtotime((string) $order['created_at']))) ?></dd>

                    <dt class="text-secondary small">Last Updated At</dt>
                    <dd class="mb-0"><?= e(date('F j, Y, g:i:s A', strtotime((string) $order['updated_at']))) ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
