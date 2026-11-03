<?php

declare(strict_types=1);

namespace App\Home;

use App\Admin\Products\ProductImageService;
use App\Core\View;

final class ProductController
{
    public function show(): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $product = $id === false ? null : (new CatalogService())->findPublished((int) $id);

        if ($product === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Fabric not found']);

            return;
        }

        View::render('product', [
            'title' => (string) $product['title'],
            'product' => $product,
            'images' => (new ProductImageService())->forProduct((int) $product['id']),
        ]);
    }
}
