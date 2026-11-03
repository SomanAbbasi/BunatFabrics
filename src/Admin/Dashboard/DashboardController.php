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
        $counts = Database::connection()->query(
            "SELECT COUNT(*) AS accounts,
                    SUM(role = 'user') AS customers,
                    SUM(role = 'admin') AS admins
             FROM users"
        )->fetch();

        View::render('admin/dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'stats' => [
                'Total accounts' => (int) ($counts['accounts'] ?? 0),
                'Customers' => (int) ($counts['customers'] ?? 0),
                'Administrators' => (int) ($counts['admins'] ?? 0),
            ],
        ], 'admin/layout');
    }
}
