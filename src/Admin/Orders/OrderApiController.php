<?php

declare(strict_types=1);

namespace App\Admin\Orders;

use App\Auth\Guard;

/**
 * Read-only JSON endpoints for the admin dashboard.
 *
 * Authentication reuses the normal admin session, so the dashboard can call
 * these routes with fetch() after an administrator signs in.
 */
final class OrderApiController
{
    private const STATUSES = ['pending', 'confirmed', 'processing', 'dispatched', 'delivered', 'rejected', 'cancelled'];

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

        $pageInput = $_GET['page'] ?? 1;
        $perPageInput = $_GET['per_page'] ?? 20;
        $page = min(1000000, max(1, is_scalar($pageInput) ? (int) $pageInput : 1));
        $perPage = min(100, max(1, is_scalar($perPageInput) ? (int) $perPageInput : 20));

        $this->json((new OrderService())->apiListing($status, $page, $perPage));
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

        $order = (new OrderService())->apiFind($id);
        if ($order === null) {
            $this->json(['error' => 'Order not found.'], 404);
            return;
        }
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
