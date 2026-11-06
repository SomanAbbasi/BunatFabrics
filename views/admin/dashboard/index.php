<?php

declare(strict_types=1);

use App\Orders\OrderService;

/** @var list<array{label: string, value: int, note: string, link?: string}> $stats */
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $recentOrders */
$recentOrders = $recentOrders ?? [];
?>
<div class="row row-deck g-3 mb-4">
    <?php foreach ($stats as $stat): ?>
        <div class="col-sm-6 col-xl-3">
            <article class="card w-100">
                <div class="card-body">
                    <h2 class="subheader"><?= e($stat['label']) ?></h2>
                    <div class="h1 mb-1"><?= e(number_format($stat['value'])) ?></div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-secondary small"><?= e($stat['note']) ?></span>
                        <?php if (! empty($stat['link'])): ?>
                            <a href="<?= e(url($stat['link'])) ?>" class="small">View &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Left Column: Orders & Catalog -->
    <div class="col-xl-8">
        <!-- Recent Wholesale Orders Section -->
        <section class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Recent Wholesale Orders</h3>
                <a href="<?= e(url('/admin/orders')) ?>" class="btn btn-sm btn-outline-primary">Manage All Orders &rarr;</a>
            </div>
            <?php if ($recentOrders === []): ?>
                <div class="card-body text-center py-4 text-secondary">
                    No orders placed yet. Placed wholesale customer orders will appear here.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Volume</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="w-1 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
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
                                        <a href="<?= e(url('/admin/orders/show?id=' . $order['id'])) ?>" class="fw-bold font-monospace">
                                            <?= e($orderNum) ?>
                                        </a>
                                        <div class="text-secondary small"><?= e(date('M d', strtotime((string) $order['created_at']))) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-medium"><?= e($order['customer_name'] ?? 'Guest') ?></div>
                                        <div class="text-secondary small"><?= e($order['contact_phone'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div><?= e((string) ($order['item_lines'] ?? 1)) ?> lines</div>
                                        <div class="text-secondary small"><?= e(number_format((int) $order['total_quantity'])) ?>m</div>
                                    </td>
                                    <td class="fw-bold">
                                        <?= e(pkr((float) $order['subtotal_pkr'])) ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= e($badgeClass) ?> text-uppercase">
                                            <?= e(OrderService::ALL_STATUSES[$status] ?? ucfirst($status)) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= e(url('/admin/orders/show?id=' . $order['id'])) ?>" class="btn btn-sm btn-ghost-primary">
                                            Slip
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <h2 class="h3 mb-3">Catalog Management</h2>
        <div class="row row-deck g-3">
            <div class="col-md-6"><section class="card w-100">
                <div class="card-header"><h3 class="card-title">Categories</h3><a class="btn btn-sm btn-outline-primary ms-auto" href="<?= e(url('/admin/categories')) ?>">Manage</a></div>
                <div class="card-body"><p class="fw-medium">Organize fabric collections.</p><p class="text-secondary mb-0">Create categories, control visibility, and keep the storefront tidy.</p></div>
            </section></div>
            <div class="col-md-6"><section class="card w-100">
                <div class="card-header"><h3 class="card-title">Products</h3><a class="btn btn-sm btn-outline-primary ms-auto" href="<?= e(url('/admin/products')) ?>">Manage</a></div>
                <div class="card-body"><p class="fw-medium">Add photos, prices, and specifications.</p><p class="text-secondary mb-0">Published fabrics in visible categories appear on the storefront.</p></div>
            </section></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <a class="btn btn-primary" href="<?= e(url('/admin/products/create')) ?>">Add product</a>
            <a class="btn" href="<?= e(url('/admin/categories/create')) ?>">Add category</a>
            <a class="btn" href="<?= e(url('/admin/orders')) ?>">View all orders</a>
            <a class="btn" href="<?= e(url('/')) ?>">View storefront</a>
        </div>
    </div>

    <!-- Right Column: Account Card -->
    <div class="col-xl-4">
        <h2 class="h3 mb-3">Account</h2>
        <section class="card mb-4"><div class="card-body">
            <h3 class="h2 mb-1"><?= e($user['name']) ?></h3>
            <span class="badge bg-green-lt mb-4">Administrator</span>
            <dl class="mb-0 admin-details">
                <dt class="text-secondary small">Email address</dt><dd class="mb-3"><?= e($user['email']) ?></dd>
                <dt class="text-secondary small">Member since</dt><dd class="mb-0"><?= e($user['created_at']) ?></dd>
            </dl>
        </div></section>
    </div>
</div>
