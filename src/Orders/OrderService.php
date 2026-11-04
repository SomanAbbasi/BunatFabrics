<?php

declare(strict_types=1);

namespace App\Orders;

use App\Cart\CartService;
use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/** Writes the same order and item snapshots read by the admin order service. */
final class OrderService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /** @param list<array<string, mixed>> $items @param array<string, string> $contact */
    public function place(int $userId, array $items, array $contact): int
    {
        if ($items === []) {
            throw new RuntimeException('Cannot place an empty order.');
        }
        $this->pdo->beginTransaction();
        try {
            $user = $this->pdo->prepare("SELECT name FROM users WHERE id = ? AND role = 'user' FOR UPDATE");
            $user->execute([$userId]);
            $name = $user->fetchColumn();
            if (! is_string($name)) {
                throw new RuntimeException('Customer account is unavailable.');
            }
            // Lock and re-check each product so a concurrent publication or price
            // change cannot produce an order different from the reviewed cart.
            $product = $this->pdo->prepare("SELECT p.id, p.product_code, p.title, p.unit, p.price_pkr
                FROM products p JOIN categories c ON c.id = p.category_id
                WHERE p.id = ? AND p.status = 'published' AND c.is_active = 1 FOR UPDATE");
            $snapshots = [];
            $subtotalCents = 0;
            foreach ($items as $item) {
                $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => CartService::MAX_QUANTITY]]);
                if ($quantity === false) {
                    throw new RuntimeException('Invalid quantity.');
                }
                $product->execute([(int) $item['id']]);
                $current = $product->fetch();
                if (! $current || self::cents((string) $current['price_pkr']) !== self::cents((string) $item['price_pkr'])
                    || (isset($item['title']) && $current['title'] !== $item['title'])
                    || (isset($item['product_code']) && $current['product_code'] !== $item['product_code'])
                    || (isset($item['unit']) && $current['unit'] !== $item['unit'])) {
                    throw new RuntimeException('The catalog changed. Please review the cart again.');
                }
                $priceCents = self::cents((string) $current['price_pkr']);
                $lineCents = $priceCents * $quantity;
                $subtotalCents += $lineCents;
                if ($subtotalCents > 999999999999) {
                    throw new RuntimeException('Order total exceeds the supported limit.');
                }
                $snapshots[] = [$current, $quantity, $priceCents, $lineCents];
            }
            // Legacy checkout databases have a writable subtotal_pkr column;
            // on the admin schema it is a generated compatibility alias.
            $columns = $this->pdo->prepare("SELECT COLUMN_NAME, EXTRA FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
            $columns->execute(['orders']);
            $orderColumns = array_column($columns->fetchAll(), 'EXTRA', 'COLUMN_NAME');
            $legacySubtotal = isset($orderColumns['subtotal_pkr']) && ! str_contains(strtoupper($orderColumns['subtotal_pkr']), 'GENERATED');
            $orderSql = "INSERT INTO orders
                (order_no, user_id, status, subtotal, delivery_name, delivery_phone, delivery_address, city, payment_method, contact_phone, shipping_address"
                . ($legacySubtotal ? ', subtotal_pkr' : '') . ") VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?"
                . ($legacySubtotal ? ', ?' : '') . ')';
            $values = [
                'BF' . strtoupper(bin2hex(random_bytes(8))), $userId, self::decimal($subtotalCents), $name,
                $contact['phone'], $contact['address'], $contact['city'], $contact['payment_method'], $contact['phone'], $contact['address'],
            ];
            if ($legacySubtotal) $values[] = self::decimal($subtotalCents);
            $order = $this->pdo->prepare($orderSql);
            $order->execute($values);
            $id = (int) $this->pdo->lastInsertId();
            $columns->execute(['order_items']);
            $itemColumns = array_column($columns->fetchAll(), 'EXTRA', 'COLUMN_NAME');
            $legacyItems = isset($itemColumns['title'], $itemColumns['unit_price_pkr'], $itemColumns['line_total_pkr']);
            $lineSql = 'INSERT INTO order_items
                (order_id, product_id, product_code, fabric_title, color_name, unit, unit_price, quantity, line_total'
                . ($legacyItems ? ', title, unit_price_pkr, line_total_pkr' : '') . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?'
                . ($legacyItems ? ', ?, ?, ?' : '') . ')';
            $line = $this->pdo->prepare($lineSql);
            foreach ($snapshots as [$current, $quantity, $priceCents, $lineCents]) {
                $values = [$id, $current['id'], $current['product_code'], $current['title'], '', $current['unit'], self::decimal($priceCents), $quantity, self::decimal($lineCents)];
                if ($legacyItems) array_push($values, $current['title'], self::decimal($priceCents), self::decimal($lineCents));
                $line->execute($values);
            }
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function cents(string $amount): int
    {
        if (! preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/D', $amount, $matches)) {
            throw new RuntimeException('Invalid catalog price.');
        }
        return (int) $matches[1] * 100 + (int) str_pad($matches[2] ?? '', 2, '0');
    }

    private static function decimal(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public function findForUser(int $orderId, int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :user_id LIMIT 1');
        $statement->execute(['id' => $orderId, 'user_id' => $userId]);
        $order = $statement->fetch();
        if ($order === false) {
            return null;
        }
        $items = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id');
        $items->execute(['order_id' => $orderId]);
        return ['order' => $order, 'items' => $items->fetchAll()];
    }
}
