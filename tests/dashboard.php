<?php

declare(strict_types=1);

require __DIR__ . '/../src/Admin/Dashboard/DashboardService.php';
require __DIR__ . '/../src/Orders/OrderService.php';

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
checkDashboard(str_contains($html, 'No orders yet.') && str_contains($html, 'Not tracked'), 'empty dashboard explains missing orders and inventory');
checkDashboard(str_contains($html, 'No delivered orders in this period.'), 'empty trend is explained');
$dashboard['recentOrders'][] = ['id' => 1, 'order_number' => '<script>', 'customer_name' => '<img>', 'created_at' => '2026-03-01', 'status' => 'pending', 'subtotal_pkr' => 20];
ob_start(); require __DIR__ . '/../views/admin/dashboard/index.php'; $html = ob_get_clean();
checkDashboard(str_contains($html, '&lt;script&gt;') && ! str_contains($html, '<img>'), 'order and customer names are escaped');
$dashboard = null;
ob_start(); require __DIR__ . '/../views/admin/dashboard/index.php'; $html = ob_get_clean();
checkDashboard(str_contains($html, 'temporarily unavailable'), 'database error has a safe message');
