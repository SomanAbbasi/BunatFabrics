<?php

declare(strict_types=1);

use App\Orders\OrderService;
?>
<div class="table-responsive" role="region" aria-label="<?= e($tableLabel ?? 'Orders') ?>" tabindex="0">
    <table class="table table-vcenter card-table dashboard-orders">
        <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Date</th><th scope="col">Amount</th><th scope="col">Status</th></tr></thead>
        <tbody>
        <?php foreach ($tableOrders as $order): ?>
            <?php
            $status = (string) $order['status'];
            $badge = match ($status) {
                'pending' => 'dashboard-status-pending',
                'confirmed', 'processing', 'dispatched' => 'dashboard-status-active',
                'delivered' => 'dashboard-status-delivered',
                default => 'dashboard-status-muted',
            };
            $number = $order['order_number'] ?: sprintf('BNT-%05d', $order['id']);
            ?>
            <tr><td><a class="dashboard-order-link" href="<?= e(url('/admin/orders/show?id=' . (int) $order['id'])) ?>"><?= e($number) ?></a></td>
                <td><?= e($order['customer_name']) ?></td><td><?= e(date('d M Y', strtotime((string) $order['created_at']))) ?></td>
                <td class="dashboard-order-amount"><?= e(pkr((float) $order['subtotal_pkr'])) ?></td><td><span class="dashboard-status <?= e($badge) ?>"><?= e(OrderService::ALL_STATUSES[$status] ?? ucfirst($status)) ?></span></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
