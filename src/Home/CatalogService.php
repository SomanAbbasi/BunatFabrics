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
        $statement = $this->pdo->prepare($this->selectPublished() . ' ORDER BY p.id DESC LIMIT 13 OFFSET ?');
        $statement->bindValue(1, ($page - 1) * 12, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();

        return ['products' => array_slice($rows, 0, 12), 'hasNext' => count($rows) > 12];
    }

    /**
     * Public products grouped into their categories for the storefront.
     *
     * @return list<array{id: int, name: string, slug: string, products: list<array<string, mixed>>}>
     */
    public function groupedPublished(): array
    {
        $statement = $this->pdo->prepare(
            $this->selectPublished() . ' ORDER BY c.name, c.id, p.id DESC'
        );
        $statement->execute();

        $groups = [];
        foreach ($statement->fetchAll() as $row) {
            $categoryId = (int) $row['category_id'];
            if (! isset($groups[$categoryId])) {
                $groups[$categoryId] = [
                    'id' => $categoryId,
                    'name' => (string) $row['category_name'],
                    'slug' => (string) $row['category_slug'],
                    'products' => [],
                ];
            }
            $groups[$categoryId]['products'][] = $row;
        }

        return array_values($groups);
    }

    public function findPublished(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            $this->selectPublished() . ' AND p.id = ? LIMIT 1'
        );
        $statement->execute([$id]);

        return $statement->fetch() ?: null;
    }

    /**
     * Fetch several published products keyed by their id.
     *
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>
     */
    public function findPublishedMany(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $statement    = $this->pdo->prepare(
            $this->selectPublished() . " AND p.id IN ({$placeholders})"
        );
        $statement->execute($ids);

        $products = [];
        foreach ($statement->fetchAll() as $row) {
            $products[(int) $row['id']] = $row;
        }

        return $products;
    }

    private function selectPublished(): string
    {
        return "SELECT p.id, p.product_code, p.title, p.description, p.composition,
            p.width_inch, p.weight_gsm, p.unit, p.price_pkr, p.category_id,
            c.name AS category_name, c.slug AS category_slug,
            (SELECT pi.file_path FROM product_images pi
             WHERE pi.product_id = p.id ORDER BY pi.sort_order, pi.id LIMIT 1) AS image_path
            FROM products p JOIN categories c ON c.id = p.category_id
            WHERE p.status = 'published' AND c.is_active = 1";
    }
}
