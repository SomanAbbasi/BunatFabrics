<?php

declare(strict_types=1);

namespace App\Orders;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Persists wholesale orders and their line items, with full status tracking.
 *
 * Product titles, codes, units and prices are copied onto the order so later
 * catalog edits never rewrite an existing order.
 */
final class OrderService
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DISPATCHED = 'dispatched';
    public const STATUS_DELIVERED  = 'delivered';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_CANCELLED  = 'cancelled';

    /**
     * Major statuses available for admin updates.
     */
    public const ALL_STATUSES = [
        self::STATUS_PENDING    => 'Pending',
        self::STATUS_CONFIRMED  => 'Confirmed',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_DISPATCHED => 'Dispatched',
        self::STATUS_DELIVERED  => 'Delivered',
        self::STATUS_REJECTED   => 'Rejected',
        self::STATUS_CANCELLED  => 'Cancelled',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * @param list<array<string, mixed>> $items cart lines with product details
     * @param array{phone: string, address: string} $contact
     */
    public function place(int $userId, array $items, float $subtotal, array $contact): int
    {
        if ($items === []) {
            throw new RuntimeException('Cannot place an empty order.');
        }

        $this->pdo->beginTransaction();

        try {
            $order = $this->pdo->prepare(
                "INSERT INTO orders (user_id, status, subtotal_pkr, contact_phone, shipping_address, order_number)
                 VALUES (:user_id, 'pending', :subtotal, :phone, :address, '')"
            );
            $order->execute([
                'user_id'  => $userId,
                'subtotal' => $subtotal,
                'phone'    => $contact['phone'],
                'address'  => $contact['address'],
            ]);

            $orderId = (int) $this->pdo->lastInsertId();
            $orderNumber = sprintf('BNT-%05d', $orderId);

            $updateNumber = $this->pdo->prepare('UPDATE orders SET order_number = :order_number WHERE id = :id');
            $updateNumber->execute([
                'order_number' => $orderNumber,
                'id'           => $orderId,
            ]);

            $line = $this->pdo->prepare(
                'INSERT INTO order_items
                    (order_id, product_id, product_code, title, unit, unit_price_pkr, quantity, line_total_pkr)
                 VALUES
                    (:order_id, :product_id, :product_code, :title, :unit, :unit_price, :quantity, :line_total)'
            );

            foreach ($items as $item) {
                $line->execute([
                    'order_id'     => $orderId,
                    'product_id'   => (int) $item['id'],
                    'product_code' => (string) $item['product_code'],
                    'title'        => (string) $item['title'],
                    'unit'         => (string) $item['unit'],
                    'unit_price'   => (float) $item['price_pkr'],
                    'quantity'     => (int) $item['quantity'],
                    'line_total'   => (float) $item['line_total'],
                ]);
            }

            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    /**
     * Load an order and its lines only when it belongs to the given customer.
     *
     * @return array{order: array<string, mixed>, items: list<array<string, mixed>>}|null
     */
    public function findForUser(int $orderId, int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $statement->execute(['id' => $orderId, 'user_id' => $userId]);
        $order = $statement->fetch();

        if ($order === false) {
            return null;
        }

        $items = $this->pdo->prepare(
            'SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id'
        );
        $items->execute(['order_id' => $orderId]);

        return ['order' => $order, 'items' => $items->fetchAll()];
    }

    /**
     * List all orders placed by a specific customer.
     *
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT o.*,
                    COUNT(oi.id) AS item_lines,
                    COALESCE(SUM(oi.quantity), 0) AS total_quantity
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = :user_id
             GROUP BY o.id
             ORDER BY o.id DESC"
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    /**
     * Find single order by ID for admin (including customer info and items).
     *
     * @return array{order: array<string, mixed>, items: list<array<string, mixed>>}|null
     */
    public function findById(int $orderId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE o.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $orderId]);
        $order = $statement->fetch();

        if ($order === false) {
            return null;
        }

        $items = $this->pdo->prepare(
            'SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id'
        );
        $items->execute(['order_id' => $orderId]);

        return ['order' => $order, 'items' => $items->fetchAll()];
    }

    /**
     * Update status and optional dispatch tracking details for an order.
     */
    public function updateStatus(
        int $orderId,
        string $status,
        ?string $trackingNumber = null,
        ?string $adminNotes = null
    ): bool {
        if (! array_key_exists($status, self::ALL_STATUSES)) {
            return false;
        }

        $fields = ['status = :status'];
        $params = [
            'id'     => $orderId,
            'status' => $status,
        ];

        if ($trackingNumber !== null) {
            $fields[] = 'tracking_number = :tracking_number';
            $params['tracking_number'] = trim($trackingNumber);
        }

        if ($adminNotes !== null) {
            $fields[] = 'admin_notes = :admin_notes';
            $params['admin_notes'] = trim($adminNotes);
        }

        $sql = 'UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $this->pdo->prepare($sql);

        return $statement->execute($params);
    }

    /**
     * Count orders by status (for dashboard summary).
     *
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $statement = $this->pdo->query(
            'SELECT status, COUNT(*) as count FROM orders GROUP BY status'
        );
        $counts = [];
        while ($row = $statement->fetch()) {
            $counts[(string) $row['status']] = (int) $row['count'];
        }

        return $counts;
    }
}
