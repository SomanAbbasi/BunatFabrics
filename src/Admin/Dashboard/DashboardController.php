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

        View::render('admin/dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'stats' => [
                ['label' => 'Products', 'value' => (int) ($products['total'] ?? 0), 'note' => (int) ($products['drafts'] ?? 0) . ' draft' . ((int) ($products['drafts'] ?? 0) === 1 ? '' : 's')],
                ['label' => 'Published', 'value' => (int) ($products['published'] ?? 0), 'note' => 'Visible in the store'],
                ['label' => 'Categories', 'value' => $categories, 'note' => 'Fabric groups'],
                ['label' => 'Customers', 'value' => (int) ($accounts['customers'] ?? 0), 'note' => (int) ($accounts['accounts'] ?? 0) . ' total accounts'],
            ],
        ], 'admin/layout');
    }
}
