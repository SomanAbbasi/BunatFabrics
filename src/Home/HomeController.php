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
        $allGroups = [];
        $activeCategory = filter_var($_GET['category'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $activeCategory = $activeCategory === false ? null : $activeCategory;

        try {
            $allGroups = (new CatalogService())->groupedPublished();
        } catch (Throwable $exception) {
            // Customers receive a useful message, never a SQL/connection exception.
            error_log('Public catalog unavailable: ' . $exception->getCode());
            http_response_code(503);
            $unavailable = true;
        }

        $groups = $allGroups;
        if ($activeCategory !== null) {
            $groups = array_values(array_filter(
                $allGroups,
                static fn (array $group): bool => $group['id'] === $activeCategory
            ));
        }

        View::render('home', [
            'title' => 'Wholesale fabric catalog',
            'allGroups' => $allGroups,
            'groups' => $groups,
            'activeCategory' => $activeCategory,
            'unavailable' => $unavailable,
        ]);
    }
}
