<?php

declare(strict_types=1);

namespace App\Admin\Orders;

use App\Admin\CatalogInput;
use App\Auth\Guard;
use App\Core\Session;
use App\Core\View;

final class OrderController
{
    public function index(): void
    {
        $user = Guard::requireAdmin();
        $filters = ['q'=>CatalogInput::text($_GET, 'q'), 'status'=>CatalogInput::text($_GET, 'status'), 'date'=>CatalogInput::text($_GET, 'date')];
        $errors = [];
        if (mb_strlen($filters['q']) > 100) $errors[] = 'Search must be 100 characters or fewer.';
        if ($filters['status'] !== '' && ! array_key_exists($filters['status'], OrderService::TRANSITIONS)) $errors[] = 'Choose a valid status.';
        if ($filters['date'] !== '') { $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $filters['date']); if (! $date || $date->format('Y-m-d') !== $filters['date']) $errors[] = 'Choose a valid date.'; }
        $page = CatalogInput::page();
        $listing = $errors === [] ? (new OrderService())->listing($filters, $page) : ['orders'=>[], 'hasNext'=>false, 'counts'=>[]];
        View::render('admin/orders/index', $listing + ['title'=>'Orders', 'activeNav'=>'orders', 'user'=>$user, 'filters'=>$filters, 'page'=>$page, 'errors'=>$errors, 'statuses'=>array_keys(OrderService::TRANSITIONS)], 'admin/layout');
    }

    public function update(): void
    {
        $user = Guard::requireAdmin(); CatalogInput::csrf();
        $id = CatalogInput::id($_POST['id'] ?? null);
        $result = $id === null ? 'invalid' : (new OrderService())->transition($id, CatalogInput::text($_POST, 'expected'), CatalogInput::text($_POST, 'status'), $user);
        Session::flash($result === 'ok' ? 'status' : 'error', match ($result) {
            'ok' => 'Order status updated.', 'stale' => 'This order changed or no longer exists. Reload and try again.', default => 'That status change is not permitted.',
        });
        redirect('/admin/orders');
    }
}
