<?php

declare(strict_types=1);

namespace App\Admin\Dashboard;

use DateTimeImmutable;
use PDO;

final class DashboardService
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{start: string, end: string, label: string, period: string} */
    public static function period(mixed $value, ?DateTimeImmutable $today = null): array
    {
        $period = is_string($value) && in_array($value, ['7', '30', '90'], true) ? $value : '30';
        $today = ($today ?? new DateTimeImmutable('today'))->setTime(0, 0);

        return [
            'start' => $today->modify('-' . ((int) $period - 1) . ' days')->format('Y-m-d H:i:s'),
            'end' => $today->modify('+1 day')->format('Y-m-d H:i:s'),
            'label' => 'Last ' . $period . ' days',
            'period' => $period,
        ];
    }

    /** @param array{start: string, end: string} $period */
    public function overview(array $period): array
    {
        $params = [$period['start'], $period['end']];
        $orders = $this->pdo->prepare("SELECT COUNT(*) AS total,
            COALESCE(SUM(status = 'delivered'), 0) AS delivered,
            COALESCE(SUM(CASE WHEN status = 'delivered' THEN subtotal_pkr ELSE 0 END), 0) AS revenue,
            AVG(CASE WHEN status = 'delivered' THEN subtotal_pkr END) AS average_value
            FROM orders WHERE created_at >= ? AND created_at < ?");
        $orders->execute($params);

        $customers = $this->pdo->prepare("SELECT COUNT(*) AS total,
            COALESCE(SUM(created_at >= ? AND created_at < ?), 0) AS new_customers
            FROM users WHERE role = 'user'");
        $customers->execute($params);

        $products = $this->pdo->prepare("SELECT COUNT(*) AS total,
            COALESCE(SUM(status = 'published'), 0) AS published,
            COALESCE(SUM(status = 'draft'), 0) AS drafts,
            COALESCE(SUM(status = 'archived'), 0) AS archived,
            COALESCE(SUM(price_pkr = 0), 0) AS zero_price FROM products");
        $products->execute();

        $attention = $this->pdo->prepare("SELECT COALESCE(SUM(status = 'pending'), 0) AS pending,
            COALESCE(SUM(status IN ('confirmed', 'processing')), 0) AS processing
            FROM orders WHERE status IN ('pending', 'confirmed', 'processing')");
        $attention->execute();

        $recent = $this->pdo->prepare("SELECT o.id, o.order_number, o.created_at, o.status, o.subtotal_pkr, u.name AS customer_name
            FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC, o.id DESC LIMIT 5");
        $recent->execute();
        $pending = $this->pdo->prepare("SELECT o.id, o.order_number, o.created_at, o.status, o.subtotal_pkr, u.name AS customer_name
            FROM orders o JOIN users u ON u.id = o.user_id WHERE o.status = 'pending' ORDER BY o.created_at ASC, o.id ASC LIMIT 5");
        $pending->execute();

        $trend = $this->pdo->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS orders,
            SUM(subtotal_pkr) AS revenue FROM orders WHERE status = 'delivered'
            AND created_at >= ? AND created_at < ? GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 7");
        $trend->execute($params);

        return ['orders' => $orders->fetch(), 'customers' => $customers->fetch(),
            'products' => $products->fetch(), 'attention' => $attention->fetch(),
            'recentOrders' => $recent->fetchAll(), 'pendingOrders' => $pending->fetchAll(),
            'trend' => $trend->fetchAll()];
    }
}
