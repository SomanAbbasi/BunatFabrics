<?php

declare(strict_types=1);

// Preview: php database/import-image-products.php
// Import:  php database/import-image-products.php --apply
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../bootstrap.php';

use App\Admin\Categories\CategoryService;
use App\Admin\Products\ProductService;
use App\Core\Database;

$catalog = json_decode(file_get_contents(__DIR__ . '/image-products.json'), true, 512, JSON_THROW_ON_ERROR);
$apply = in_array('--apply', $argv, true);
$mimeTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$sources = [];
$createdFiles = [];
$createdDirectories = [];
$pdo = null;

try {
    foreach ($catalog['products'] as $product) {
        $name = $product['image'];
        if (basename($name) !== $name) {
            throw new RuntimeException('Invalid image filename.');
        }
        $catalogRoot = realpath(BASE_PATH . '/public/uploads/catalog');
        $source = realpath(BASE_PATH . '/public/' . ($product['image_path'] ?? ''));
        if ($catalogRoot === false || $source === false
            || ! str_starts_with($source, $catalogRoot . DIRECTORY_SEPARATOR)
            || ! is_file($source) || ! is_readable($source) || getimagesize($source) === false) {
            throw new RuntimeException('Missing or invalid image: ' . $name);
        }
        $mime = $finfo->file($source);
        if (! isset($mimeTypes[$mime]) || filesize($source) > 2_000_000) {
            throw new RuntimeException('Unsupported image type or size: ' . $name);
        }
        $sources[$name] = [$source, $mimeTypes[$mime]];
    }

    if (! $apply) {
        echo $catalog['pricing_note'] . PHP_EOL;
        foreach ($catalog['products'] as $product) {
            echo $product['product_code'] . ' | ' . $product['title'] . ' | '
                . $product['category_slug'] . ' | PKR ' . $product['price_pkr'] . '/meter' . PHP_EOL;
        }
        echo 'Validated ' . count($sources) . ' images. Use --apply to create missing categories and products. Existing product codes are preserved.' . PHP_EOL;
        exit;
    }

    $pdo = Database::connection();
    $pdo->beginTransaction();
    $categories = new CategoryService($pdo);
    $products = new ProductService($pdo);
    $categoryIds = [];
    $counts = ['categories_created' => 0, 'products_created' => 0, 'products_existing' => 0, 'images_added' => 0];

    foreach ($catalog['categories'] as $category) {
        $statement = $pdo->prepare('SELECT id FROM categories WHERE slug = ? OR name = ?');
        $statement->execute([$category['slug'], $category['name']]);
        $matches = $statement->fetchAll();
        if (count($matches) > 1) {
            throw new RuntimeException('Ambiguous category: ' . $category['name']);
        }
        if ($matches !== []) {
            $id = (int) $matches[0]['id'];
        } else {
            $result = $categories->save($category);
            if ($result['errors'] !== []) {
                throw new RuntimeException(json_encode($result['errors']));
            }
            $id = (int) $result['id'];
            $counts['categories_created']++;
        }
        $categoryIds[$category['slug']] = $id;
    }

    foreach ($catalog['products'] as $product) {
        $statement = $pdo->prepare('SELECT id, category_id FROM products WHERE product_code = ?');
        $statement->execute([$product['product_code']]);
        $existing = $statement->fetch();
        $categoryId = $categoryIds[$product['category_slug']];
        if ($existing) {
            $id = (int) $existing['id'];
            $categoryId = (int) $existing['category_id'];
            $counts['products_existing']++;
        } else {
            $result = $products->save($product + ['category_id' => (string) $categoryId]);
            if ($result['errors'] !== []) {
                throw new RuntimeException(json_encode($result['errors']));
            }
            $id = (int) $result['id'];
            $counts['products_created']++;
        }

        [$source, $extension] = $sources[$product['image']];
        $relativeDirectory = 'uploads/catalog/' . $categoryId . '/product-' . $id;
        $relativePath = $relativeDirectory . '/' . hash_file('sha256', $source) . '.' . $extension;
        $destination = BASE_PATH . '/public/' . $relativePath;
        $statement = $pdo->prepare('SELECT id FROM product_images WHERE product_id = ? AND (file_path = ? OR original_name = ?)');
        $statement->execute([$id, $relativePath, $product['image']]);
        if ($statement->fetchColumn() !== false) {
            continue;
        }

        foreach (['uploads', 'uploads/catalog', 'uploads/catalog/' . $categoryId, $relativeDirectory] as $directory) {
            $absolute = BASE_PATH . '/public/' . $directory;
            if (! is_dir($absolute)) {
                if (! mkdir($absolute, 0775)) {
                    throw new RuntimeException('Cannot create image directory: ' . $directory);
                }
                $createdDirectories[] = $absolute;
            }
        }
        if (! is_file($destination)) {
            $createdFiles[] = $destination;
            if (! copy($source, $destination)) {
                throw new RuntimeException('Cannot copy image: ' . $product['image']);
            }
        }
        if (hash_file('sha256', $source) !== hash_file('sha256', $destination)) {
            throw new RuntimeException('Image verification failed: ' . $product['image']);
        }
        $statement = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_images WHERE product_id = ?');
        $statement->execute([$id]);
        $sortOrder = (int) $statement->fetchColumn();
        $statement = $pdo->prepare('INSERT INTO product_images (product_id, file_path, original_name, sort_order) VALUES (?, ?, ?, ?)');
        $statement->execute([$id, $relativePath, $product['image'], $sortOrder]);
        $counts['images_added']++;
    }

    $pdo->commit();
    echo json_encode($counts, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $exception) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach ($createdFiles as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    foreach (array_reverse($createdDirectories) as $directory) {
        if (is_dir($directory) && count(scandir($directory)) === 2) {
            rmdir($directory);
        }
    }
    fwrite(STDERR, 'Import failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
