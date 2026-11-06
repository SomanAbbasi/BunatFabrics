<?php

declare(strict_types=1);

use App\Orders\OrderService;

/** @var array<string, mixed>|null $dashboard */
/** @var array{start: string, end: string, label: string, period: string} $period */
?>
<div class="business-dashboard">
    <div class="dashboard-intro">
        <div>
            <p class="dashboard-kicker">Bunat Fabrics / Administration</p>
            <h2>Know where your business stands.</h2>
            <p>Review sales, orders, and your fabric catalog in one place.</p>
        </div>
        <form method="get" action="<?= e(url('/admin')) ?>" class="dashboard-period">
            <label for="dashboard-period">Reporting period</label>
            <div class="dashboard-period-controls">
                <select class="form-select" id="dashboard-period" name="period">
                    <?php foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $period['period'] === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Apply</button>
            </div>
        </form>
    </div>

    <?php if ($dashboard === null): ?>
        <div class="alert alert-danger" role="alert">Business data is temporarily unavailable. Please try again later.</div>
    <?php else: ?>
        <?php
        $orders = $dashboard['orders'];
        $attention = $dashboard['attention'];
        $products = $dashboard['products'];
        $customers = $dashboard['customers'];
        $cards = [
            ['Revenue', pkr((float) $orders['revenue']), 'Delivered orders only'],
            ['Orders received', number_format((int) $orders['total']), 'All order statuses'],
            ['Delivered orders', number_format((int) $orders['delivered']), 'By date order was placed'],
            ['Average order value', $orders['average_value'] === null ? '—' : pkr((float) $orders['average_value']), 'Delivered orders only'],
        ];
        ?>
        <div class="dashboard-section-heading"><div><h2>Sales overview</h2><p><?= e($period['label']) ?> · <?= e(date('d M Y', strtotime($period['start']))) ?> – <?= e(date('d M Y', strtotime($period['end'] . ' -1 day'))) ?></p></div></div>
        <div class="dashboard-kpis">
            <?php foreach ($cards as [$label, $value, $note]): ?>
                <article class="card dashboard-kpi"><div class="card-body"><h3><?= e($label) ?></h3><p class="dashboard-kpi-value"><?= e($value) ?></p><p><?= e($note) ?></p></div></article>
            <?php endforeach; ?>
        </div>
        <p class="dashboard-caption">Revenue is the subtotal of orders currently marked delivered, counted by order date. It excludes pending, active, rejected and cancelled orders; it does not represent payments received.</p>

        <div class="dashboard-grid">
            <section class="card dashboard-main" aria-labelledby="attention-heading">
                <div class="dashboard-card-header"><div><p class="dashboard-kicker">Order queue · all time</p><h2 id="attention-heading">Orders needing review</h2><p><?= e(number_format((int) $attention['pending'])) ?> pending · <?= e(number_format((int) $attention['processing'])) ?> confirmed or processing</p></div><a class="btn btn-outline-primary btn-sm" href="<?= e(url('/admin/orders?status=pending')) ?>">Review orders</a></div>
                <?php if ($dashboard['pendingOrders'] === []): ?>
                    <div class="dashboard-empty"><strong>No orders waiting for review.</strong><span>New pending orders will appear here.</span></div>
                <?php else: ?>
                    <?php $tableOrders = $dashboard['pendingOrders']; require __DIR__ . '/orders-table.php'; ?>
                <?php endif; ?>
            </section>

            <section class="card dashboard-side" aria-labelledby="inventory-heading">
                <div class="dashboard-card-header"><div><p class="dashboard-kicker">Stock visibility</p><h2 id="inventory-heading">Inventory alerts</h2></div></div>
                <div class="card-body dashboard-inventory"><span class="dashboard-neutral-badge">Not tracked</span><p>Stock quantities and alert thresholds are not currently recorded, so low-stock and out-of-stock alerts are unavailable.</p><a href="<?= e(url('/admin/products')) ?>">View products &rarr;</a></div>
            </section>

            <section class="card dashboard-main" aria-labelledby="recent-heading">
                <div class="dashboard-card-header"><div><p class="dashboard-kicker">Latest activity · all time</p><h2 id="recent-heading">Recent orders</h2></div><a class="btn btn-outline-primary btn-sm" href="<?= e(url('/admin/orders')) ?>">All orders</a></div>
                <?php if ($dashboard['recentOrders'] === []): ?>
                    <div class="dashboard-empty"><strong>No orders yet.</strong><span>Orders will appear once customers submit them.</span></div>
                <?php else: ?>
                    <?php $tableOrders = $dashboard['recentOrders']; require __DIR__ . '/orders-table.php'; ?>
                <?php endif; ?>
            </section>

            <div class="dashboard-side dashboard-stack">
                <section class="card" aria-labelledby="catalog-heading"><div class="card-body">
                    <p class="dashboard-kicker">Your collection · all time</p><h2 id="catalog-heading">Catalog overview</h2>
                    <dl class="dashboard-list"><div><dt>Total fabrics</dt><dd><?= e(number_format((int) $products['total'])) ?></dd></div><div><dt>Published</dt><dd><?= e(number_format((int) $products['published'])) ?></dd></div><div><dt>Draft / archived</dt><dd><?= e(number_format((int) $products['drafts'])) ?> / <?= e(number_format((int) $products['archived'])) ?></dd></div><div><dt>Zero price to review</dt><dd><?= e(number_format((int) $products['zero_price'])) ?></dd></div></dl>
                    <p class="dashboard-caption">A zero price may be intentional; review it before publishing.</p><div class="dashboard-links"><a href="<?= e(url('/admin/products')) ?>">Manage products &rarr;</a><a href="<?= e(url('/admin/categories')) ?>">Categories &rarr;</a></div>
                </div></section>
                <section class="card" aria-labelledby="customers-heading"><div class="card-body">
                    <p class="dashboard-kicker">Customer activity</p><h2 id="customers-heading">Registered customers</h2>
                    <dl class="dashboard-list"><div><dt>Total customers</dt><dd><?= e(number_format((int) $customers['total'])) ?></dd></div><div><dt>New · <?= e(strtolower($period['label'])) ?></dt><dd><?= e(number_format((int) $customers['new_customers'])) ?></dd></div></dl>
                    <p class="dashboard-caption">Customer counts exclude administrator accounts.</p>
                </div></section>
            </div>
        </div>

        <div class="dashboard-bottom">
            <section class="card" aria-labelledby="trend-heading"><div class="card-body"><p class="dashboard-kicker"><?= e($period['label']) ?></p><h2 id="trend-heading">Delivered sales by order date</h2>
                <?php if ($dashboard['trend'] === []): ?><p class="dashboard-caption mb-0">No delivered orders in this period. Sales will appear here when orders are delivered.</p>
                <?php else: ?><div class="table-responsive"><table class="table table-vcenter dashboard-trend"><thead><tr><th scope="col">Order date</th><th scope="col">Delivered orders</th><th scope="col">Subtotal</th></tr></thead><tbody>
                    <?php foreach ($dashboard['trend'] as $day): ?><tr><td><?= e(date('d M Y', strtotime((string) $day['day']))) ?></td><td><?= e((string) $day['orders']) ?></td><td><?= e(pkr((float) $day['revenue'])) ?></td></tr><?php endforeach; ?>
                </tbody></table></div><p class="dashboard-caption mb-0">Showing the latest seven days with delivered orders.</p><?php endif; ?>
            </div></section>
            <section class="card" aria-labelledby="actions-heading"><div class="card-body"><p class="dashboard-kicker">Go straight to work</p><h2 id="actions-heading">Quick actions</h2><div class="dashboard-actions"><a href="<?= e(url('/admin/products/create')) ?>">Add a fabric <span>→</span></a><a href="<?= e(url('/admin/orders')) ?>">Manage orders <span>→</span></a><a href="<?= e(url('/admin/settings')) ?>">Account settings <span>→</span></a><a href="<?= e(url('/')) ?>">View storefront <span>→</span></a></div></div></section>
        </div>
    <?php endif; ?>
</div>
