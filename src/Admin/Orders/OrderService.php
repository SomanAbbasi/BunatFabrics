<?php

declare(strict_types=1);

namespace App\Admin\Orders;

use App\Core\Database;
use PDO;
use Throwable;

final class OrderService
{
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'rejected'], 'confirmed' => ['processing', 'cancelled'],
        'processing' => ['dispatched'], 'dispatched' => ['delivered'],
        'delivered' => [], 'rejected' => [], 'cancelled' => [],
    ];
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) { $this->pdo = $pdo ?? Database::connection(); }

    public function listing(array $filters, int $page): array
    {
        $where = ['1=1']; $params = [];
        if ($filters['q'] !== '') {
            $where[] = '(o.order_no LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
            $term = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']) . '%';
            $where[count($where)-1] = "(o.order_no LIKE ? ESCAPE '!' OR u.name LIKE ? ESCAPE '!' OR u.email LIKE ? ESCAPE '!')";
            array_push($params, $term, $term, $term);
        }
        if ($filters['status'] !== '') { $where[] = 'o.status = ?'; $params[] = $filters['status']; }
        if ($filters['date'] !== '') { $where[] = 'o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY)'; array_push($params, $filters['date'], $filters['date']); }
        $sql = 'SELECT o.*, u.name AS customer, u.email FROM orders o JOIN users u ON u.id=o.user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY o.id DESC LIMIT 21 OFFSET ?';
        $st = $this->pdo->prepare($sql);
        foreach ($params as $i => $value) { $st->bindValue($i + 1, $value); }
        $st->bindValue(count($params) + 1, ($page - 1) * 20, PDO::PARAM_INT); $st->execute();
        $rows = $st->fetchAll(); $next = count($rows) > 20; $rows = array_slice($rows, 0, 20);
        foreach ($rows as &$row) {
            $st = $this->pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id'); $st->execute([$row['id']]); $row['items'] = $st->fetchAll();
            $st = $this->pdo->prepare('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id DESC'); $st->execute([$row['id']]); $row['history'] = $st->fetchAll();
            $row['next'] = self::TRANSITIONS[$row['status']] ?? [];
        }
        unset($row);
        $statement = $this->pdo->prepare("SELECT COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status IN ('confirmed','processing','dispatched')) AS active FROM orders");
        $statement->execute();
        $counts = $statement->fetch();
        return ['orders'=>$rows, 'hasNext'=>$next, 'counts'=>$counts];
    }

    /** Return the existing JSON API shape from the same order records. */
    public function apiListing(?string $status, int $page, int $perPage): array
    {
        $where = $status === null ? '' : 'WHERE o.status = :status';
        $count = $this->pdo->prepare("SELECT COUNT(*) FROM orders o {$where}");
        if ($status !== null) $count->bindValue(':status', $status);
        $count->execute();
        $total = (int) $count->fetchColumn();
        $statement = $this->pdo->prepare("SELECT o.id, o.order_no, o.user_id, o.status,
            o.subtotal AS subtotal_pkr, o.delivery_phone AS contact_phone,
            o.delivery_address AS shipping_address, o.created_at, o.updated_at,
            u.name AS customer_name, u.email AS customer_email,
            COUNT(oi.id) AS item_lines, COALESCE(SUM(oi.quantity), 0) AS total_quantity
            FROM orders o JOIN users u ON u.id = o.user_id
            LEFT JOIN order_items oi ON oi.order_id = o.id {$where}
            GROUP BY o.id ORDER BY o.id DESC LIMIT :limit OFFSET :offset");
        if ($status !== null) $statement->bindValue(':status', $status);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        return ['data' => $statement->fetchAll(), 'meta' => [
            'page' => $page, 'per_page' => $perPage, 'total' => $total,
            'total_pages' => $total === 0 ? 0 : (int) ceil($total / $perPage), 'status' => $status,
        ]];
    }

    public function apiFind(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email
            FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ? LIMIT 1');
        $statement->execute([$id]);
        $order = $statement->fetch();
        if ($order === false) return null;
        $items = $this->pdo->prepare('SELECT id, product_id, product_code, fabric_title AS title, unit,
            unit_price AS unit_price_pkr, quantity, line_total AS line_total_pkr
            FROM order_items WHERE order_id = ? ORDER BY id');
        $items->execute([$id]);
        $order['subtotal_pkr'] = $order['subtotal'];
        $order['contact_phone'] = $order['delivery_phone'];
        $order['shipping_address'] = $order['delivery_address'];
        $order['items'] = $items->fetchAll();
        return $order;
    }

    public function transition(int $id, string $expected, string $next, array $admin): string
    {
        if (! in_array($next, self::TRANSITIONS[$expected] ?? [], true)) return 'invalid';
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('UPDATE orders SET status = ? WHERE id = ? AND status = ?');
            $st->execute([$next, $id, $expected]);
            if ($st->rowCount() !== 1) { $this->pdo->rollBack(); return 'stale'; }
            $st = $this->pdo->prepare('INSERT INTO order_status_history (order_id, old_status, new_status, changed_by, admin_name) VALUES (?, ?, ?, ?, ?)');
            $st->execute([$id, $expected, $next, $admin['id'], $admin['name']]);
            $this->pdo->commit(); return 'ok';
        } catch (Throwable $exception) { $this->pdo->rollBack(); throw $exception; }
    }
}
