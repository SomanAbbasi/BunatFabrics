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
        $counts = $this->pdo->query("SELECT COUNT(*) AS total, SUM(status='pending') AS pending, SUM(status IN ('confirmed','processing','dispatched')) AS active FROM orders")->fetch();
        return ['orders'=>$rows, 'hasNext'=>$next, 'counts'=>$counts];
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
