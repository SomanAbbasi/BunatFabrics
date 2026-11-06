<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\View;
use App\Orders\OrderService;

/**
 * The authenticated area. Admins and regular customers see different screens.
 */
final class DashboardController
{
    private OrderService $orders;

    public function __construct(?OrderService $orders = null)
    {
        $this->orders = $orders ?? new OrderService();
    }

    public function index(): void
    {
        $user = Guard::user();

        if ($user === null) {
            redirect('/login');
        }

        if (Guard::isAdmin()) {
            redirect('/admin');
        }

        $userOrders = $this->orders->listForUser((int) $user['id']);

        View::render('dashboard/user', [
            'title'  => 'Dashboard',
            'user'   => $user,
            'orders' => $userOrders,
        ]);
    }
}
