<?php

declare(strict_types=1);

namespace App\Orders;

use App\Auth\Guard;
use App\Core\View;

/**
 * Customer order history and order tracking screens.
 */
final class CustomerOrderController
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

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pagination = $this->orders->paginateForUser((int) $user['id'], $page, 10);

        View::render('orders/index', [
            'title'      => 'My Orders',
            'user'       => $user,
            'orders'     => $pagination['data'],
            'page'       => $pagination['page'],
            'totalPages' => $pagination['totalPages'],
            'total'      => $pagination['total'],
        ]);
    }

    public function show(): void
    {
        $user = Guard::user();

        if ($user === null) {
            redirect('/login');
        }

        $orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $result  = $orderId === false ? null : $this->orders->findForUser((int) $orderId, (int) $user['id']);

        if ($result === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Order not found']);

            return;
        }

        View::render('orders/show', [
            'title' => 'Order ' . ($result['order']['order_number'] ?: '#' . $result['order']['id']),
            'user'  => $user,
            'order' => $result['order'],
            'items' => $result['items'],
        ]);
    }
}
