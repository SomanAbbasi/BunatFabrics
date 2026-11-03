<?php

declare(strict_types=1);

namespace App\Admin\Categories;

use App\Admin\CatalogInput;
use App\Core\Database;
use PDO;
use PDOException;

final class CategoryService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function listing(int $page): array
    {
        $statement = $this->pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY c.id DESC LIMIT 21 OFFSET ?');
        $statement->bindValue(1, ($page - 1) * 20, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        return ['items' => array_slice($rows, 0, 20), 'hasNext' => count($rows) > 20];
    }

    public function options(): array
    {
        return $this->pdo->query('SELECT id, name, is_active FROM categories ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function save(array $input, ?int $id = null): array
    {
        $data = [];
        foreach (['name', 'slug', 'description', 'is_active'] as $key) {
            $data[$key] = CatalogInput::text($input, $key);
        }
        $data['slug'] = strtolower($data['slug']);
        $errors = [];
        if ($data['name'] === '' || mb_strlen($data['name']) > 100) {
            $errors['name'] = 'Enter a category name of 1–100 characters.';
        }
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $data['slug']) || strlen($data['slug']) > 120) {
            $errors['slug'] = 'Use up to 120 lowercase letters, numbers, and single hyphens.';
        }
        if (mb_strlen($data['description']) > 5000) {
            $errors['description'] = 'Use no more than 5,000 characters.';
        }
        if (! in_array($data['is_active'], ['0', '1'], true)) {
            $errors['is_active'] = 'Choose visible or hidden.';
        }
        foreach (['name', 'slug'] as $column) {
            // Column names come only from this fixed allowlist.
            $statement = $this->pdo->prepare("SELECT id FROM categories WHERE {$column} = ? AND id <> ?");
            $statement->execute([$data[$column], $id ?? 0]);
            if ($statement->fetchColumn() !== false) {
                $errors[$column] = 'This ' . $column . ' is already in use.';
            }
        }
        if ($errors === []) {
            try {
                if ($id === null) {
                    $statement = $this->pdo->prepare('INSERT INTO categories (name, slug, description, is_active) VALUES (:name, :slug, :description, :is_active)');
                    $statement->execute($data);
                    $id = (int) $this->pdo->lastInsertId();
                } else {
                    $statement = $this->pdo->prepare('UPDATE categories SET name = :name, slug = :slug, description = :description, is_active = :is_active WHERE id = :id');
                    $statement->execute($data + ['id' => $id]);
                }
            } catch (PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) !== 1062) {
                    throw $exception;
                }
                $errors['name'] = 'The name or slug was just used by another category. Choose unique values.';
            }
        }
        return ['data' => $data, 'errors' => $errors, 'id' => $id];
    }

    public function setVisibility(int $id, string $state): bool
    {
        if (! in_array($state, ['0', '1'], true)) {
            return false;
        }
        $statement = $this->pdo->prepare('UPDATE categories SET is_active = ? WHERE id = ?');
        $statement->execute([$state, $id]);
        return true;
    }
}
