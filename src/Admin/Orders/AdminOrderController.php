<?php

declare(strict_types=1);

namespace App\Admin\Orders;

use App\Auth\Guard;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Orders\OrderService;
use PDO;

/**
 * Controller for the Admin Orders Management screens.
 */
final class AdminOrderController
{
    private PDO $pdo;
    private OrderService $orderService;

    public function __construct(?PDO $pdo = null, ?OrderService $orderService = null)
    {
        $this->pdo = $pdo ?? Database::connection();
        $this->orderService = $orderService ?? new OrderService($this->pdo);
    }

    public function index(): void
    {
        $user = Guard::requireAdmin();

        $status = trim((string) ($_GET['status'] ?? ''));
        $search = trim((string) ($_GET['search'] ?? ''));
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 15;

        $clauses = [];
        $params  = [];

        if ($status !== '' && array_key_exists($status, OrderService::ALL_STATUSES)) {
            $clauses[] = 'o.status = :status';
            $params[':status'] = $status;
        }

        if ($search !== '') {
            $clauses[] = '(o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR o.contact_phone LIKE :search OR o.id = :search_exact)';
            $params[':search'] = '%' . $search . '%';
            $params[':search_exact'] = (int) $search;
        }

        $where = $clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses);

        // Count total matching
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id {$where}");
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalOrders = (int) $countStmt->fetchColumn();
        $totalPages = $totalOrders > 0 ? (int) ceil($totalOrders / $perPage) : 1;

        // Fetch paginated orders
        $offset = ($page - 1) * $perPage;
        $orderStmt = $this->pdo->prepare(
            "SELECT o.*,
                    u.name AS customer_name,
                    u.email AS customer_email,
                    COUNT(oi.id) AS item_lines,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity
             FROM orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN order_items oi ON oi.order_id = o.id
             {$where}
             GROUP BY o.id
             ORDER BY o.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $k => $v) {
            $orderStmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $orderStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $orderStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $orderStmt->execute();
        $orders = $orderStmt->fetchAll();

        // Overall stats
        $summary = $this->pdo->query(
            "SELECT
                COUNT(*) AS total_count,
                COALESCE(SUM(status = 'pending'), 0) AS pending_count,
                COALESCE(SUM(status = 'confirmed' OR status = 'processing'), 0) AS processing_count,
                COALESCE(SUM(status = 'dispatched'), 0) AS dispatched_count,
                COALESCE(SUM(status = 'delivered'), 0) AS delivered_count,
                COALESCE(SUM(status = 'rejected' OR status = 'cancelled'), 0) AS rejected_count,
                COALESCE(SUM(subtotal_pkr), 0) AS total_revenue
             FROM orders"
        )->fetch();

        View::render('admin/orders/index', [
            'title'         => 'Orders Management',
            'user'          => $user,
            'activeNav'     => 'orders',
            'orders'        => $orders,
            'summary'       => $summary,
            'currentStatus' => $status,
            'searchQuery'   => $search,
            'page'          => $page,
            'totalPages'    => $totalPages,
            'totalOrders'   => $totalOrders,
        ], 'admin/layout');
    }

    public function show(): void
    {
        $user = Guard::requireAdmin();

        $orderId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($orderId === false || $orderId === null) {
            Session::flash('error', 'Invalid order ID.');
            redirect('/admin/orders');
        }

        $result = $this->orderService->findById((int) $orderId);
        if ($result === null) {
            Session::flash('error', 'Order not found.');
            redirect('/admin/orders');
        }

        View::render('admin/orders/show', [
            'title'     => 'Order ' . ($result['order']['order_number'] ?: '#' . $result['order']['id']),
            'user'      => $user,
            'activeNav' => 'orders',
            'order'     => $result['order'],
            'items'     => $result['items'],
        ], 'admin/layout');
    }

    public function updateStatus(): void
    {
        Guard::requireAdmin();

        if (! Session::verifyCsrf($_POST['_token'] ?? null)) {
            Session::flash('error', 'Session expired. Please try again.');
            redirect('/admin/orders');
        }

        $orderId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status  = trim((string) ($_POST['status'] ?? ''));
        $trackingNumber = isset($_POST['tracking_number']) ? trim((string) $_POST['tracking_number']) : null;
        $adminNotes     = isset($_POST['admin_notes']) ? trim((string) $_POST['admin_notes']) : null;

        if ($orderId === false || $orderId === null || ! array_key_exists($status, OrderService::ALL_STATUSES)) {
            Session::flash('error', 'Invalid order ID or status selection.');
            redirect('/admin/orders');
        }

        $updated = $this->orderService->updateStatus((int) $orderId, $status, $trackingNumber, $adminNotes);

        if ($updated) {
            $statusName = OrderService::ALL_STATUSES[$status] ?? ucfirst($status);
            Session::flash('status', "Order status successfully updated to {$statusName}.");
        } else {
            Session::flash('error', 'Failed to update order status.');
        }

        $returnTo = trim((string) ($_POST['return_to'] ?? ''));
        if ($returnTo !== '' && str_starts_with($returnTo, '/admin/orders')) {
            redirect($returnTo);
        }

        redirect('/admin/orders/show?id=' . $orderId);
    }
}
