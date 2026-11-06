<?php

declare(strict_types=1);

namespace App\Admin\Dashboard;

use App\Auth\Guard;
use App\Core\Database;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        // Authorization happens before any dashboard data is queried.
        $user = Guard::requireAdmin();
        $pdo = Database::connection();
        $accounts = $pdo->query(
            "SELECT COUNT(*) AS accounts,
                    COALESCE(SUM(role = 'user'), 0) AS customers
             FROM users"
        )->fetch();
        $products = $pdo->query(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(status = 'published'), 0) AS published,
                    COALESCE(SUM(status = 'draft'), 0) AS drafts
             FROM products"
        )->fetch();
        $categories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();

        $ordersSummary = $pdo->query(
            "SELECT COUNT(*) AS total_orders,
                    COALESCE(SUM(status = 'pending'), 0) AS pending_orders,
                    COALESCE(SUM(subtotal_pkr), 0) AS total_revenue
             FROM orders"
        )->fetch();

        $recentOrders = $pdo->query(
            "SELECT o.*, u.name AS customer_name,
                    COUNT(oi.id) AS item_lines,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity
             FROM orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             GROUP BY o.id
             ORDER BY o.id DESC
             LIMIT 5"
        )->fetchAll();

        View::render('admin/dashboard/index', [
            'title'        => 'Dashboard',
            'user'         => $user,
            'recentOrders' => $recentOrders,
            'stats'        => [
                ['label' => 'Total Orders', 'value' => (int) ($ordersSummary['total_orders'] ?? 0), 'note' => (int) ($ordersSummary['pending_orders'] ?? 0) . ' pending review', 'link' => '/admin/orders'],
                ['label' => 'Published Fabrics', 'value' => (int) ($products['published'] ?? 0), 'note' => (int) ($products['total'] ?? 0) . ' total in catalog', 'link' => '/admin/products'],
                ['label' => 'Categories', 'value' => $categories, 'note' => 'Fabric collections', 'link' => '/admin/categories'],
                ['label' => 'Customers', 'value' => (int) ($accounts['customers'] ?? 0), 'note' => (int) ($accounts['accounts'] ?? 0) . ' total accounts', 'link' => ''],
            ],
        ], 'admin/layout');
    }
}
