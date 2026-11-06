<?php

declare(strict_types=1);

namespace App\Admin\Dashboard;

use App\Auth\Guard;
use App\Core\Database;
use App\Core\View;
use Throwable;

final class DashboardController
{
    public function index(): void
    {
        $user = Guard::requireAdmin();
        $period = DashboardService::period($_GET['period'] ?? null);
        $data = null;
        try {
            $data = (new DashboardService(Database::connection()))->overview($period);
        } catch (Throwable $exception) {
            error_log('Dashboard unavailable: ' . $exception->getCode());
            http_response_code(503);
        }

        View::render('admin/dashboard/index', [
            'title' => 'Business overview', 'user' => $user, 'period' => $period, 'dashboard' => $data,
        ], 'admin/layout');
    }
}
