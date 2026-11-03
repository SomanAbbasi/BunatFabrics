<?php

declare(strict_types=1);

namespace App\Home;

use App\Core\Database;
use PDO;

final class CatalogService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function published(int $page): array
    {
        $statement = $this->pdo->prepare("SELECT p.product_code, p.title, p.description, p.composition,
            p.width_inch, p.weight_gsm, p.unit, c.name AS category_name
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE p.status = 'published' AND c.is_active = 1
            ORDER BY p.id DESC LIMIT 13 OFFSET ?");
        $statement->bindValue(1, ($page - 1) * 12, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        return ['products' => array_slice($rows, 0, 12), 'hasNext' => count($rows) > 12];
    }
}
