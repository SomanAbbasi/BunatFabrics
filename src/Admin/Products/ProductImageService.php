<?php

declare(strict_types=1);

namespace App\Admin\Products;

use App\Core\Database;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Stores product images under public/uploads/catalog/{category}/product-{id}.
 *
 * The product id keeps each folder stable when a product code changes. When a
 * product moves to another category the folder is moved and every stored path
 * is rewritten, so the images stay linked to the product.
 */
final class ProductImageService
{
    public const MAX_FILES = 6;
    public const MAX_BYTES = 2_000_000;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }

    /**
     * Validate the uploaded files before the product record is written.
     *
     * @return list<string>
     */
    public function validate(array $files): array
    {
        $uploads = $this->uploads($files);

        if ($uploads === []) {
            return [];
        }

        if (count($uploads) > self::MAX_FILES) {
            return ['Upload no more than ' . self::MAX_FILES . ' images per product.'];
        }

        $errors = [];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        foreach ($uploads as $upload) {
            if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = 'One of the selected images could not be uploaded.';
                continue;
            }
            $size = (int) ($upload['size'] ?? 0);
            if ($size <= 0 || $size > self::MAX_BYTES) {
                $errors[] = 'Each image must be 2 MB or smaller.';
                continue;
            }
            $tmp = (string) ($upload['tmp_name'] ?? '');
            if ($tmp === '' || ! is_uploaded_file($tmp)) {
                $errors[] = 'One of the selected images was not received correctly.';
                continue;
            }
            $mime = $finfo->file($tmp) ?: '';
            if (! isset(self::MIME_EXTENSIONS[$mime]) || @getimagesize($tmp) === false) {
                $errors[] = 'Images must be JPG, PNG, or WebP files.';
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Move validated uploads into the product folder and record them.
     *
     * @return list<string>
     */
    public function store(int $productId, int $categoryId, array $files): array
    {
        $uploads = $this->uploads($files);
        if ($uploads === []) {
            return [];
        }

        $relativeDirectory = $this->relativeDirectory($categoryId, $productId);
        $absoluteDirectory = BASE_PATH . '/public/' . $relativeDirectory;

        if (! is_dir($absoluteDirectory) && ! mkdir($absoluteDirectory, 0755, true) && ! is_dir($absoluteDirectory)) {
            return ['Unable to create the product image folder.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $sort = (int) $this->pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ' . $productId)->fetchColumn();
        $errors = [];

        foreach ($uploads as $upload) {
            $tmp = (string) ($upload['tmp_name'] ?? '');
            if ($tmp === '' || ! is_uploaded_file($tmp)) {
                $errors[] = 'One of the selected images was not received correctly.';
                continue;
            }
            $mime = $finfo->file($tmp) ?: '';
            $extension = self::MIME_EXTENSIONS[$mime] ?? null;
            if ($extension === null) {
                $errors[] = 'Images must be JPG, PNG, or WebP files.';
                continue;
            }

            $fileName = bin2hex(random_bytes(12)) . '.' . $extension;
            $relativePath = $relativeDirectory . '/' . $fileName;

            if (! move_uploaded_file($tmp, BASE_PATH . '/public/' . $relativePath)) {
                $errors[] = 'Unable to save one of the uploaded images.';
                continue;
            }

            $sort++;
            $statement = $this->pdo->prepare(
                'INSERT INTO product_images (product_id, file_path, original_name, sort_order)
                 VALUES (?, ?, ?, ?)'
            );
            $statement->execute([
                $productId,
                $relativePath,
                mb_substr((string) ($upload['name'] ?? ''), 0, 255),
                $sort,
            ]);
        }

        return $errors;
    }

    public function forProduct(int $productId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id'
        );
        $statement->execute([$productId]);

        return $statement->fetchAll();
    }

    public function delete(int $imageId, int $productId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT file_path FROM product_images WHERE id = ? AND product_id = ?'
        );
        $statement->execute([$imageId, $productId]);
        $path = $statement->fetchColumn();

        if ($path === false) {
            return false;
        }

        $statement = $this->pdo->prepare(
            'DELETE FROM product_images WHERE id = ? AND product_id = ?'
        );
        $statement->execute([$imageId, $productId]);

        $absolute = BASE_PATH . '/public/' . ltrim((string) $path, '/');
        if (str_starts_with($absolute, BASE_PATH . '/public/uploads/') && is_file($absolute)) {
            @unlink($absolute);
        }

        return true;
    }

    /**
     * Keep files grouped by the product's current category. Paths in the
     * database are rewritten only after the folder move succeeds.
     */
    public function moveForCategory(int $productId, int $oldCategoryId, int $newCategoryId): void
    {
        if ($oldCategoryId === $newCategoryId) {
            return;
        }

        $oldRelative = $this->relativeDirectory($oldCategoryId, $productId);
        $newRelative = $this->relativeDirectory($newCategoryId, $productId);
        $oldDirectory = BASE_PATH . '/public/' . $oldRelative;
        $newDirectory = BASE_PATH . '/public/' . $newRelative;

        if (! is_dir($oldDirectory)) {
            return;
        }

        try {
            $parent = dirname($newDirectory);
            if (! is_dir($parent) && ! mkdir($parent, 0755, true) && ! is_dir($parent)) {
                throw new RuntimeException('Cannot create category image folder.');
            }
            if (is_dir($newDirectory)) {
                $this->mergeDirectory($oldDirectory, $newDirectory);
            } elseif (! rename($oldDirectory, $newDirectory)) {
                throw new RuntimeException('Cannot move product image folder.');
            }

            $statement = $this->pdo->prepare(
                'UPDATE product_images SET file_path = REPLACE(file_path, ?, ?) WHERE product_id = ?'
            );
            $statement->execute([$oldRelative . '/', $newRelative . '/', $productId]);

            $this->removeEmptyDirectory(dirname($oldDirectory));
        } catch (Throwable $exception) {
            error_log('Product image relink failed: ' . $exception->getMessage());
        }
    }

    public static function url(string $filePath): string
    {
        return url('/' . ltrim($filePath, '/'));
    }

    /**
     * @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private function uploads(array $files): array
    {
        if (! isset($files['images']) || ! is_array($files['images'])) {
            return [];
        }

        $field = $files['images'];
        $names = is_array($field['name'] ?? null) ? $field['name'] : [$field['name'] ?? ''];

        $uploads = [];
        foreach ($names as $index => $name) {
            if ((string) $name === '') {
                continue;
            }
            $uploads[] = [
                'name' => (string) $name,
                'type' => (string) (is_array($field['type'] ?? null) ? ($field['type'][$index] ?? '') : ''),
                'tmp_name' => (string) (is_array($field['tmp_name'] ?? null) ? ($field['tmp_name'][$index] ?? '') : ''),
                'error' => (int) (is_array($field['error'] ?? null) ? ($field['error'][$index] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE),
                'size' => (int) (is_array($field['size'] ?? null) ? ($field['size'][$index] ?? 0) : 0),
            ];
        }

        return $uploads;
    }

    private function relativeDirectory(int $categoryId, int $productId): string
    {
        return 'uploads/catalog/' . $categoryId . '/product-' . $productId;
    }

    private function mergeDirectory(string $source, string $target): void
    {
        foreach ((array) glob($source . '/*') as $file) {
            if (is_file($file) && ! rename($file, $target . '/' . basename($file))) {
                throw new RuntimeException('Cannot move an existing product image.');
            }
        }
    }

    private function removeEmptyDirectory(string $directory): void
    {
        if (is_dir($directory) && (array) glob($directory . '/*') === []) {
            @rmdir($directory);
        }
    }
}
