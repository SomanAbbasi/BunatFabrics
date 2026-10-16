<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\View;

/**
 * The authenticated area. Admins and regular customers see different screens.
 */
final class DashboardController
{
    public function index(): void
    {
        $user = Guard::user();

        if ($user === null) {
            redirect('/login');
        }

        $view = Guard::isAdmin() ? 'dashboard/admin' : 'dashboard/user';

        View::render($view, [
            'title' => Guard::isAdmin() ? 'Admin dashboard' : 'Dashboard',
            'user'  => $user,
            'stats' => $this->stats(),
        ]);
    }

    /**
     * Simple counters shown on the dashboard cards.
     *
     * @return array{users: int, admins: int}
     */
    private function stats(): array
    {
        if (! Guard::isAdmin()) {
            return ['users' => 0, 'admins' => 0];
        }

        $counts = $this->pdo()->query(
            "SELECT
                COUNT(*) AS users,
                SUM(role = 'admin') AS admins
             FROM users"
        )->fetch();

        return [
            'users'  => (int) ($counts['users'] ?? 0),
            'admins' => (int) ($counts['admins'] ?? 0),
        ];
    }

    private function pdo(): \PDO
    {
        return \App\Core\Database::connection();
    }
}
