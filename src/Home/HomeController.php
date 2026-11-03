<?php

declare(strict_types=1);

namespace App\Home;

use App\Core\View;
use Throwable;

final class HomeController
{
    public function index(): void
    {
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]) ?: 1;
        $unavailable = false;
        try {
            $listing = (new CatalogService())->published($page);
        } catch (Throwable $exception) {
            // Customers receive a useful message, never a SQL/connection exception.
            error_log('Public catalog unavailable: ' . $exception->getCode());
            http_response_code(503);
            $unavailable = true;
            $listing = ['products' => [], 'hasNext' => false];
        }
        View::render('home', $listing + [
            'title' => 'Fabric catalog', 'page' => $page, 'unavailable' => $unavailable,
        ]);
    }
}
