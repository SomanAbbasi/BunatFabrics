<?php

declare(strict_types=1);

namespace App\Home;

use App\Core\View;
use Throwable;

final class HomeController
{
    public function index(): void
    {
        $unavailable = false;
        try {
            $groups = (new CatalogService())->groupedPublished();
        } catch (Throwable $exception) {
            // Customers receive a useful message, never a SQL/connection exception.
            error_log('Public catalog unavailable: ' . $exception->getCode());
            http_response_code(503);
            $unavailable = true;
            $groups = [];
        }

        View::render('home', [
            'title' => 'Wholesale fabric catalog',
            'groups' => $groups,
            'unavailable' => $unavailable,
        ]);
    }
}
