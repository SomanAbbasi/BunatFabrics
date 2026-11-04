<?php

declare(strict_types=1);

namespace App\Admin\Products;

use App\Admin\CatalogInput;
use App\Core\Database;
use PDO;
use PDOException;

final class ProductService
{
    public const STATUSES = ['draft', 'published', 'archived'];
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    public function listing(int $page): array
    {
        $statement = $this->pdo->prepare('SELECT p.*, c.name AS category_name, c.is_active AS category_active, (SELECT pi.file_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order, pi.id LIMIT 1) AS image_path FROM products p JOIN categories c ON c.id = p.category_id ORDER BY p.id DESC LIMIT 21 OFFSET ?');
        $statement->bindValue(1, ($page - 1) * 20, PDO::PARAM_INT);
        $statement->execute();
        $rows = $statement->fetchAll();
        return ['items' => array_slice($rows, 0, 20), 'hasNext' => count($rows) > 20];
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM products WHERE id = ?');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function save(array $input, ?int $id = null): array
    {
        $data = [];
        foreach (['product_code', 'title', 'description', 'category_id', 'composition', 'width_inch', 'weight_gsm', 'unit', 'price_pkr', 'status'] as $key) {
            $data[$key] = CatalogInput::text($input, $key, $key === 'price_pkr' ? '0' : '');
        }
        $data['product_code'] = strtoupper($data['product_code']);
        $data['price_pkr'] = $data['price_pkr'] === '' ? '0' : $data['price_pkr'];
        $errors = [];
        if (! preg_match('/^[A-Z0-9][A-Z0-9_-]{0,49}$/D', $data['product_code'])) {
            $errors['product_code'] = 'Use 1–50 letters, numbers, hyphens, or underscores; start with a letter or number.';
        }
        if ($data['title'] === '' || mb_strlen($data['title']) > 150) {
            $errors['title'] = 'Enter a product title of 1–150 characters.';
        }
        if ($data['description'] === '' || mb_strlen($data['description']) > 5000) {
            $errors['description'] = 'Enter a description of 1–5,000 characters.';
        }
        if (mb_strlen($data['composition']) > 100) {
            $errors['composition'] = 'Use no more than 100 characters.';
        }
        if ($data['width_inch'] !== '' && (! preg_match('/^[0-9]{1,4}(?:\.[0-9])?$/D', $data['width_inch']) || ! preg_match('/[1-9]/', $data['width_inch']))) {
            $errors['width_inch'] = 'Enter a width from 0.1 to 9999.9 inches with at most one decimal place.';
        }
        if ($data['weight_gsm'] !== '' && (! preg_match('/^[1-9][0-9]{0,4}$/D', $data['weight_gsm']) || (int) $data['weight_gsm'] > 65535)) {
            $errors['weight_gsm'] = 'Enter a whole GSM value from 1 to 65535.';
        }
        if (! preg_match('/^[0-9]{1,10}(?:\.[0-9]{1,2})?$/D', $data['price_pkr']) || (float) $data['price_pkr'] > 9999999999.99) {
            $errors['price_pkr'] = 'Enter a price in PKR using up to 10 digits and 2 decimal places.';
        }
        if (! in_array($data['unit'], ['meter', 'yard'], true)) {
            $errors['unit'] = 'Choose meter or yard.';
        }
        if (! in_array($data['status'], self::STATUSES, true)) {
            $errors['status'] = 'Choose draft, published, or archived.';
        }
        $categoryId = CatalogInput::id($data['category_id']);
        $statement = $this->pdo->prepare('SELECT id FROM categories WHERE id = ?');
        $statement->execute([$categoryId ?? 0]);
        if ($statement->fetchColumn() === false) {
            $errors['category_id'] = 'Choose an existing category.';
        }
        $statement = $this->pdo->prepare('SELECT id FROM products WHERE product_code = ? AND id <> ?');
        $statement->execute([$data['product_code'], $id ?? 0]);
        if ($statement->fetchColumn() !== false) {
            $errors['product_code'] = 'This product code is already in use.';
        }
        if ($errors === []) {
            $values = $data;
            foreach (['composition', 'width_inch', 'weight_gsm'] as $key) {
                $values[$key] = $values[$key] === '' ? null : $values[$key];
            }
            try {
                if ($id === null) {
                    $statement = $this->pdo->prepare('INSERT INTO products (product_code, title, description, category_id, composition, width_inch, weight_gsm, unit, price_pkr, status) VALUES (:product_code, :title, :description, :category_id, :composition, :width_inch, :weight_gsm, :unit, :price_pkr, :status)');
                    $statement->execute($values);
                    $id = (int) $this->pdo->lastInsertId();
                } else {
                    $statement = $this->pdo->prepare('UPDATE products SET product_code = :product_code, title = :title, description = :description, category_id = :category_id, composition = :composition, width_inch = :width_inch, weight_gsm = :weight_gsm, unit = :unit, price_pkr = :price_pkr, status = :status WHERE id = :id');
                    $statement->execute($values + ['id' => $id]);
                }
            } catch (PDOException $exception) {
                $code = $exception->errorInfo[1] ?? null;
                if ($code === 1062) {
                    $errors['product_code'] = 'This product code is already in use.';
                } elseif ($code === 1452) {
                    $errors['category_id'] = 'This category is no longer available.';
                } else {
                    throw $exception;
                }
            }
        }
        return ['data' => $data, 'errors' => $errors, 'id' => $id];
    }

    public function setStatus(int $id, string $status): bool
    {
        if (! in_array($status, self::STATUSES, true)) {
            return false;
        }
        $statement = $this->pdo->prepare('UPDATE products SET status = ? WHERE id = ?');
        $statement->execute([$status, $id]);
        return true;
    }
}
