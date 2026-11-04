<?php

declare(strict_types=1);

namespace App\Orders;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Persists wholesale orders and their line items.
 *
 * Product titles, codes, units and prices are copied onto the order so later
 * catalog edits never rewrite an existing order.
 */
final class OrderService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * @param list<array<string, mixed>> $items cart lines with product details
     */
    public function place(int $userId, array $items, float $subtotal): int
    {
        if ($items === []) {
            throw new RuntimeException('Cannot place an empty order.');
        }

        $this->pdo->beginTransaction();

        try {
            $order = $this->pdo->prepare(
                "INSERT INTO orders (user_id, status, subtotal_pkr)
                 VALUES (:user_id, 'pending', :subtotal)"
            );
            $order->execute([
                'user_id'  => $userId,
                'subtotal' => $subtotal,
            ]);

            $orderId = (int) $this->pdo->lastInsertId();

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
}
