<?php

declare(strict_types=1);

use App\Core\Session;
use App\Orders\OrderService;

/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $orders */
/** @var array<string, mixed> $summary */
/** @var string $currentStatus */
/** @var string $searchQuery */
/** @var int $page */
/** @var int $totalPages */
/** @var int $totalOrders */
?>
<!-- KPI / Summary Cards -->
<div class="row row-deck g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="admin-metric-label">Total Orders</div>
                </div>
                <div class="h1 mb-1"><?= e(number_format((int) ($summary['total_count'] ?? 0))) ?></div>
                <div class="text-secondary small">Wholesale mill requests</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="admin-metric-label">Pending Review</div>
                </div>
                <div class="h1 mb-1"><?= e(number_format((int) ($summary['pending_count'] ?? 0))) ?></div>
                <div class="text-secondary small">Awaiting bolt confirmation</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="admin-metric-label">Dispatched</div>
                </div>
                <div class="h1 mb-1"><?= e(number_format((int) ($summary['dispatched_count'] ?? 0))) ?></div>
                <div class="text-secondary small">En route to apparel brands</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="admin-metric-label">Order value</div>
                </div>
                <div class="h1 mb-1"><?= e(pkr((float) ($summary['total_revenue'] ?? 0))) ?></div>
                <div class="text-secondary small">Placed order subtotal</div>
            </div>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h2 class="card-title mb-0">Wholesale Orders (<?= e((string) $totalOrders) ?>)</h2>

        <!-- Search Form -->
        <form action="<?= e(url('/admin/orders')) ?>" method="GET" class="admin-search">
            <?php if ($currentStatus !== ''): ?>
                <input type="hidden" name="status" value="<?= e($currentStatus) ?>">
            <?php endif; ?>
            <div class="input-icon">
                <input type="text" name="search" value="<?= e($searchQuery) ?>" class="form-control form-control-sm" placeholder="Search by ID, Name, Phone..." aria-label="Search orders by ID, name or phone">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Search</button>
            <?php if ($searchQuery !== '' || $currentStatus !== ''): ?>
                <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Status Filter Tabs -->
    <div class="border-bottom px-3 pt-2 bg-light-subtle">
        <ul class="nav nav-tabs border-0 gap-1">
            <li class="nav-item">
                <a class="nav-link py-2 <?= $currentStatus === '' ? 'active' : '' ?>" href="<?= e(url('/admin/orders' . ($searchQuery ? '?search=' . urlencode($searchQuery) : ''))) ?>">
                    All Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 <?= $currentStatus === 'pending' ? 'active' : '' ?>" href="<?= e(url('/admin/orders?status=pending' . ($searchQuery ? '&search=' . urlencode($searchQuery) : ''))) ?>">
                    Pending <span class="badge bg-warning-lt ms-1"><?= (int) ($summary['pending_count'] ?? 0) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 <?= $currentStatus === 'confirmed' ? 'active' : '' ?>" href="<?= e(url('/admin/orders?status=confirmed' . ($searchQuery ? '&search=' . urlencode($searchQuery) : ''))) ?>">
                    Confirmed
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 <?= $currentStatus === 'dispatched' ? 'active' : '' ?>" href="<?= e(url('/admin/orders?status=dispatched' . ($searchQuery ? '&search=' . urlencode($searchQuery) : ''))) ?>">
                    Dispatched <span class="badge bg-purple-lt ms-1"><?= (int) ($summary['dispatched_count'] ?? 0) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 <?= $currentStatus === 'delivered' ? 'active' : '' ?>" href="<?= e(url('/admin/orders?status=delivered' . ($searchQuery ? '&search=' . urlencode($searchQuery) : ''))) ?>">
                    Delivered <span class="badge bg-success-lt ms-1"><?= (int) ($summary['delivered_count'] ?? 0) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 <?= $currentStatus === 'rejected' ? 'active' : '' ?>" href="<?= e(url('/admin/orders?status=rejected' . ($searchQuery ? '&search=' . urlencode($searchQuery) : ''))) ?>">
                    Rejected
                </a>
            </li>
        </ul>
    </div>

    <?php if ($orders === []): ?>
        <div class="card-body text-center py-5">
            <p class="text-secondary mb-2">No wholesale orders found matching criteria.</p>
            <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-sm btn-outline-primary">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="table-responsive" role="region" aria-label="Wholesale orders" tabindex="0">
            <table class="table table-vcenter card-table table-striped table-hover">
                <thead>
                    <tr>
                        <th scope="col">Order ID</th>
                        <th scope="col">Customer / Brand</th>
                        <th scope="col">Date & Time</th>
                        <th scope="col">Wholesale Items</th>
                        <th scope="col">Subtotal (PKR)</th>
                        <th scope="col">Status</th>
                        <th scope="col">Update Status</th>
                        <th scope="col" class="w-1 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
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
                        ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('/admin/orders/show?id=' . $order['id'])) ?>" class="fw-bold text-reset font-monospace">
                                    <?= e($orderNum) ?>
                                </a>
                                <?php if (! empty($order['tracking_number'])): ?>
                                    <div class="small text-secondary font-monospace">
                                        Track: <?= e($order['tracking_number']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-medium"><?= e($order['customer_name'] ?? 'Guest Customer') ?></div>
                                <div class="small text-secondary"><?= e($order['customer_email'] ?? '') ?></div>
                                <?php if (! empty($order['contact_phone'])): ?>
                                    <div class="small text-secondary"><?= e($order['contact_phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary small">
                                <div><?= e(date('M d, Y', strtotime((string) $order['created_at']))) ?></div>
                                <div><?= e(date('h:i A', strtotime((string) $order['created_at']))) ?></div>
                            </td>
                            <td>
                                <div><strong><?= e((string) ($order['item_lines'] ?? 1)) ?></strong> line items</div>
                                <div class="small text-secondary"><?= e(number_format((int) $order['total_quantity'])) ?> meters/yards</div>
                            </td>
                            <td class="fw-bold">
                                <?= e(pkr((float) $order['subtotal_pkr'])) ?>
                            </td>
                            <td>
                                <span class="badge <?= e($badgeClass) ?> text-uppercase">
                                    <?= e(OrderService::ALL_STATUSES[$status] ?? ucfirst($status)) ?>
                                </span>
                            </td>
                            <td>
                                <form action="<?= e(url('/admin/orders/status')) ?>" method="POST" class="d-inline-flex align-items-center gap-1">
                                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e((string) $order['id']) ?>">
                                    <input type="hidden" name="return_to" value="<?= e($_SERVER['REQUEST_URI'] ?? '/admin/orders') ?>">
                                    <select name="status" aria-label="<?= e('Update status for order ' . $orderNum) ?>" class="form-select form-select-sm py-1" style="font-size: 0.8rem; width: 125px;" onchange="this.form.submit()">
                                        <?php foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'dispatched' => 'Dispatched', 'delivered' => 'Delivered', 'rejected' => 'Rejected'] as $val => $label): ?>
                                            <option value="<?= e($val) ?>" <?= $status === $val ? 'selected' : '' ?>>
                                                <?= e($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <noscript><button type="submit" class="btn btn-sm btn-light">Save</button></noscript>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="<?= e(url('/admin/orders/show?id=' . $order['id'])) ?>" class="btn btn-sm btn-outline-primary">
                                    View Slip
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <span class="text-secondary small">Showing page <?= e((string) $page) ?> of <?= e((string) $totalPages) ?></span>
                <ul class="pagination m-0 ms-auto">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= e(url('/admin/orders?page=' . $p . ($currentStatus ? '&status=' . urlencode($currentStatus) : '') . ($searchQuery ? '&search=' . urlencode($searchQuery) : ''))) ?>">
                                <?= e((string) $p) ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
