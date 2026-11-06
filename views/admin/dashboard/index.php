<?php

declare(strict_types=1);

/** @var array<string, mixed>|null $dashboard */
/** @var array{start: string, end: string, label: string, period: string} $period */
// The shared layout uses this flag only to style this page's existing shell.
$dashboardPage = true;
?>
<div class="business-dashboard">
    <header class="dashboard-intro">
        <div>
            <p class="dashboard-kicker">Wholesale operations</p>
            <h1>Business overview</h1>
            <p>Your orders, sales and fabric collection at a glance.</p>
        </div>
        <nav class="dashboard-quick-actions" aria-label="Dashboard actions">
            <a class="btn btn-outline-primary" href="<?= e(url('/admin/orders')) ?>">Manage orders</a>
            <a class="btn btn-primary" href="<?= e(url('/admin/products/create')) ?>"><?= icon('plus') ?> Add fabric</a>
        </nav>
    </header>

    <section class="card dashboard-sales" aria-labelledby="sales-heading">
        <div class="dashboard-sales-heading">
            <div>
                <h2 id="sales-heading">Sales overview</h2>
                <p class="dashboard-caption">Orders placed <?= e(date('d M Y', strtotime($period['start']))) ?> – <?= e(date('d M Y', strtotime($period['end'] . ' -1 day'))) ?></p>
            </div>
            <form method="get" action="<?= e(url('/admin')) ?>" class="dashboard-period">
                <label class="visually-hidden" for="dashboard-period">Sales reporting period</label>
                <select class="form-select" id="dashboard-period" name="period">
                    <?php foreach (['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $period['period'] === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Apply</button>
            </form>
        </div>

        <?php if ($dashboard === null): ?>
            <div class="dashboard-empty" role="alert">
                <strong>Business data is temporarily unavailable.</strong>
                <span>Please try again later. You can still use the navigation to access your business tools.</span>
            </div>
        <?php else: ?>
            <?php
            $orders = $dashboard['orders'];
            $attention = $dashboard['attention'];
            $products = $dashboard['products'];
            $customers = $dashboard['customers'];
            $cards = [
                ['Delivered sales', pkr((float) $orders['revenue']), 'Delivered orders only', 'layers'],
                ['Orders received', number_format((int) $orders['total']), 'All order statuses', 'package'],
                ['Delivered orders', number_format((int) $orders['delivered']), 'By date order was placed', 'check-circle'],
                ['Average order value', $orders['average_value'] === null ? '—' : pkr((float) $orders['average_value']), 'Delivered orders only', 'cart'],
            ];
            ?>
            <div class="dashboard-kpis">
                <?php foreach ($cards as [$label, $value, $note, $symbol]): ?>
                    <article class="dashboard-kpi">
                        <div class="dashboard-kpi-label"><h3><?= e($label) ?></h3><?= icon($symbol) ?></div>
                        <p class="dashboard-kpi-value">
                            <?php if (str_starts_with($value, 'PKR ')): ?>
                                <span class="dashboard-currency">PKR</span> <?= e(substr($value, 4)) ?>
                            <?php else: ?>
                                <?= e($value) ?>
                            <?php endif; ?>
                        </p>
                        <p class="dashboard-caption"><?= e($note) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="dashboard-sales-note">Sales and average value include delivered orders only, by order date. These figures do not indicate payments received.</p>
        <?php endif; ?>
    </section>

    <?php if ($dashboard !== null): ?>
        <div class="dashboard-grid">
            <div class="dashboard-main dashboard-stack">
                <section class="card dashboard-attention" aria-labelledby="attention-heading">
                    <div class="dashboard-card-header">
                        <div>
                            <p class="dashboard-kicker">Open orders · all dates</p>
                            <h2 id="attention-heading">Pending review <span class="dashboard-count"><?= e(number_format((int) $attention['pending'])) ?></span></h2>
                        </div>
                        <a class="dashboard-link" href="<?= e(url('/admin/orders?status=pending')) ?>">Review orders <?= icon('arrow-right') ?></a>
                    </div>
                    <p class="dashboard-queue-note"><?= e(number_format((int) $attention['processing'])) ?> confirmed or processing</p>
                    <?php if ($dashboard['pendingOrders'] === []): ?>
                        <div class="dashboard-empty dashboard-empty-success">
                            <span class="dashboard-empty-icon"><?= icon('check-circle') ?></span>
                            <div><strong>No orders waiting for review.</strong><span>You're up to date. Follow active orders in <a href="<?= e(url('/admin/orders')) ?>">order management</a>.</span></div>
                        </div>
                    <?php else: ?>
                        <?php $tableOrders = $dashboard['pendingOrders']; $tableLabel = 'Orders pending review'; require __DIR__ . '/orders-table.php'; ?>
                    <?php endif; ?>
                </section>

                <section class="card" aria-labelledby="recent-heading">
                    <div class="dashboard-card-header">
                        <div><p class="dashboard-kicker">Latest five · all dates</p><h2 id="recent-heading">Recent orders</h2></div>
                        <a class="dashboard-link" href="<?= e(url('/admin/orders')) ?>">All orders <?= icon('arrow-right') ?></a>
                    </div>
                    <?php if ($dashboard['recentOrders'] === []): ?>
                        <div class="dashboard-empty"><strong>No orders yet.</strong><span>Orders will appear once customers submit them.</span></div>
                    <?php else: ?>
                        <?php $tableOrders = $dashboard['recentOrders']; $tableLabel = 'Recent orders'; require __DIR__ . '/orders-table.php'; ?>
                    <?php endif; ?>
                </section>

                <?php if ($dashboard['trend'] !== []): ?>
                    <details class="card dashboard-sales-details">
                        <summary>View delivered sales by day <span class="dashboard-summary-note"><?= e($period['label']) ?></span></summary>
                        <div class="card-body">
                            <p class="dashboard-caption">Latest seven order dates with delivered sales.</p>
                            <div class="table-responsive" role="region" aria-label="Delivered sales by day" tabindex="0">
                                <table class="table table-vcenter dashboard-trend">
                                    <thead><tr><th scope="col">Order date</th><th scope="col">Delivered orders</th><th scope="col">Subtotal</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($dashboard['trend'] as $day): ?>
                                            <tr><td><?= e(date('d M Y', strtotime((string) $day['day']))) ?></td><td><?= e((string) $day['orders']) ?></td><td><?= e(pkr((float) $day['revenue'])) ?></td></tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </details>
                <?php endif; ?>
            </div>

            <div class="dashboard-side dashboard-stack">
                <section class="card" aria-labelledby="catalog-heading">
                    <div class="dashboard-card-header">
                        <div><p class="dashboard-kicker">Current catalog</p><h2 id="catalog-heading">Fabric collection</h2></div>
                        <span class="dashboard-section-icon"><?= icon('fabric') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="dashboard-catalog-total"><strong><?= e(number_format((int) $products['total'])) ?></strong><span>Total fabrics</span></div>
                        <dl class="dashboard-list">
                            <div><dt>Published</dt><dd><?= e(number_format((int) $products['published'])) ?></dd></div>
                            <div><dt>Drafts</dt><dd><?= e(number_format((int) $products['drafts'])) ?></dd></div>
                            <div><dt>Archived</dt><dd><?= e(number_format((int) $products['archived'])) ?></dd></div>
                        </dl>
                        <?php if ((int) $products['zero_price'] > 0): ?>
                            <p class="dashboard-price-note"><?= e(number_format((int) $products['zero_price'])) ?> fabrics have a zero price. Check whether these prices are intentional.</p>
                        <?php endif; ?>
                    </div>
                    <nav class="dashboard-card-footer" aria-label="Catalog management">
                        <a class="dashboard-link" href="<?= e(url('/admin/products')) ?>">Manage products <?= icon('arrow-right') ?></a>
                        <a href="<?= e(url('/admin/categories')) ?>">Categories</a>
                    </nav>
                </section>

                <section class="card" aria-labelledby="customers-heading">
                    <div class="dashboard-card-header">
                        <div><p class="dashboard-kicker">Customer activity</p><h2 id="customers-heading">Your customers</h2></div>
                        <span class="dashboard-section-icon"><?= icon('user') ?></span>
                    </div>
                    <div class="card-body">
                        <dl class="dashboard-customer-metrics">
                            <div><dt>Total registered</dt><dd><?= e(number_format((int) $customers['total'])) ?></dd></div>
                            <div><dt>New · <?= e(strtolower($period['label'])) ?></dt><dd><?= e(number_format((int) $customers['new_customers'])) ?></dd></div>
                        </dl>
                        <p class="dashboard-caption">Customer counts exclude administrator accounts.</p>
                    </div>
                </section>

                <a class="dashboard-settings" href="<?= e(url('/admin/settings')) ?>">
                    <?= icon('lock') ?><span><strong>Account settings</strong><small>Manage your profile and password</small></span><?= icon('arrow-right') ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
