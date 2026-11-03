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

        if (Guard::isAdmin()) {
            redirect('/admin');
        }

        View::render('dashboard/user', [
            'title' => 'Dashboard',
            'user' => $user,
        ]);
    }
}
