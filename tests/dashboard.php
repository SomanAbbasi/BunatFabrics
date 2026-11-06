<?php

declare(strict_types=1);

require __DIR__ . '/../src/Admin/Dashboard/DashboardService.php';
require __DIR__ . '/../src/Orders/OrderService.php';
require __DIR__ . '/../src/Core/helpers.php';

use App\Admin\Dashboard\DashboardService;

function checkDashboard(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function url(string $path): string { return $path; }
function pkr(float $value): string { return 'PKR ' . number_format($value, 2); }

$today = new DateTimeImmutable('2026-03-01');
$period = DashboardService::period('7', $today);
checkDashboard($period['start'] === '2026-02-23 00:00:00' && $period['end'] === '2026-03-02 00:00:00', 'seven-day period includes today across month boundary');
checkDashboard(DashboardService::period('90', $today)['start'] === '2025-12-02 00:00:00', 'ninety-day period crosses year boundary');
checkDashboard(DashboardService::period(['30'], $today)['period'] === '30', 'invalid period uses safe default');
checkDashboard(DashboardService::period('999', $today)['period'] === '30', 'unsupported period uses safe default');

$dashboard = [
    'orders' => ['revenue' => 0, 'total' => 0, 'delivered' => 0, 'average_value' => null],
    'attention' => ['pending' => 0, 'processing' => 0],
    'products' => ['total' => 0, 'published' => 0, 'drafts' => 0, 'archived' => 0, 'zero_price' => 0],
    'customers' => ['total' => 0, 'new_customers' => 0],
    'recentOrders' => [], 'pendingOrders' => [], 'trend' => [],
];
ob_start(); require __DIR__ . '/../views/admin/dashboard/index.php'; $html = ob_get_clean();
checkDashboard(str_contains($html, 'No orders yet.') && ! str_contains($html, 'Inventory') && ! str_contains($html, 'Not tracked'), 'empty dashboard has no unsupported inventory section');
checkDashboard(! str_contains($html, 'View delivered sales by day') && ! str_contains($html, 'fabrics have a zero price'), 'empty sales breakdown and zero-count price notices are hidden');
$dashboard['recentOrders'][] = ['id' => 1, 'order_number' => '<script>', 'customer_name' => '<img>', 'created_at' => '2026-03-01', 'status' => 'pending', 'subtotal_pkr' => 20];
ob_start(); require __DIR__ . '/../views/admin/dashboard/index.php'; $html = ob_get_clean();
checkDashboard(str_contains($html, '&lt;script&gt;') && ! str_contains($html, '<img>'), 'order and customer names are escaped');
$dashboard['products']['zero_price'] = 2;
$dashboard['trend'][] = ['day' => '2026-03-01', 'orders' => 1, 'revenue' => 20];
ob_start(); require __DIR__ . '/../views/admin/dashboard/index.php'; $html = ob_get_clean();
checkDashboard(str_contains($html, '2 fabrics have a zero price') && str_contains($html, 'View delivered sales by day'), 'existing sales and price notices remain available');
checkDashboard(str_contains($html, 'href="/admin/settings"') && str_contains($html, 'href="/admin/orders?status=pending"'), 'primary actions use existing admin routes');
$dashboard = null;
ob_start(); require __DIR__ . '/../views/admin/dashboard/index.php'; $html = ob_get_clean();
checkDashboard(str_contains($html, 'temporarily unavailable'), 'database error has a safe message');

// Shared styling must preserve each admin page's own heading.
require_once __DIR__ . '/../src/Core/Session.php';
if (! defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}
$user = ['name' => 'Test Admin', 'email' => 'admin@example.test', 'role' => 'admin'];
$title = 'Products';
$content = '<p>Existing product content</p>';
$dashboardPage = false;
ob_start(); require __DIR__ . '/../views/admin/layout.php'; $layout = ob_get_clean();
checkDashboard(str_contains($layout, 'class="admin-shell"') && str_contains($layout, '<h1 class="page-title">Products</h1>'), 'other admin pages use the shared shell and retain their page heading');
$dashboardPage = true;
$content = '<h1>Business overview</h1>';
ob_start(); require __DIR__ . '/../views/admin/layout.php'; $layout = ob_get_clean();
checkDashboard(str_contains($layout, 'admin-business-page') && substr_count($layout, '<h1') === 1, 'dashboard has one main heading and its page flag');
