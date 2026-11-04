<?php

declare(strict_types=1);

namespace App\Admin\Orders;

use App\Auth\Guard;
use App\Core\Database;
use PDO;

/**
 * Read-only JSON endpoints for the admin dashboard.
 *
 * Authentication reuses the normal admin session, so the dashboard can call
 * these routes with fetch() after an administrator signs in.
 */
final class OrderApiController
{
    private const STATUSES = ['pending', 'confirmed', 'cancelled'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * GET /api/admin/orders?status=pending&page=1&per_page=20
     */
    public function index(): void
    {
        if (! $this->authorize()) {
            return;
        }

        $status = $_GET['status'] ?? null;
        if ($status !== null && ! in_array($status, self::STATUSES, true)) {
            $this->json(['error' => 'Unknown order status.'], 422);

            return;
        }

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($_GET['per_page'] ?? 20)));

        $where = $status === null ? '' : 'WHERE o.status = :status';

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM orders o {$where}");
        if ($status !== null) {
            $count->bindValue(':status', $status);
        }
        $count->execute();
        $total = (int) $count->fetchColumn();

        $statement = $this->pdo->prepare(
            "SELECT o.id, o.user_id, o.status, o.subtotal_pkr, o.contact_phone,
                    o.shipping_address, o.created_at, o.updated_at,
                    u.name AS customer_name, u.email AS customer_email,
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

        if ($status !== null) {
            $statement->bindValue(':status', $status);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();

        $this->json([
            'data' => $statement->fetchAll(),
            'meta' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage),
                'status'      => $status,
            ],
        ]);
    }

    /**
     * GET /api/admin/orders/show?id=1
     */
    public function show(): void
    {
        if (! $this->authorize()) {
            return;
        }

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($id === false) {
            $this->json(['error' => 'A valid order id is required.'], 422);

            return;
        }

        $statement = $this->pdo->prepare(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $order = $statement->fetch();

        if ($order === false) {
            $this->json(['error' => 'Order not found.'], 404);

            return;
        }

        $items = $this->pdo->prepare(
            'SELECT id, product_id, product_code, title, unit,
                    unit_price_pkr, quantity, line_total_pkr
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id'
        );
        $items->execute(['order_id' => $id]);

        $order['items'] = $items->fetchAll();

        $this->json(['data' => $order]);
    }

    private function authorize(): bool
    {
        if (! Guard::check()) {
            $this->json(['error' => 'Authentication required.'], 401);

            return false;
        }

        if (! Guard::isAdmin()) {
            $this->json(['error' => 'Administrator access required.'], 403);

            return false;
        }

        return true;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
