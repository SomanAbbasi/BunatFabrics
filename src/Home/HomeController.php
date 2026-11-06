<?php

declare(strict_types=1);

namespace App\Home;

use App\Cart\CartService;
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
        $search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
        $search = mb_substr($search, 0, 100);

        try {
            $allGroups = (new CatalogService())->groupedPublished();
        } catch (Throwable $exception) {
            // Customers receive a useful message, never a SQL/connection exception.
            error_log('Public catalog unavailable: ' . $exception->getCode());
            http_response_code(503);
            $unavailable = true;
        }

        $groups = $allGroups;
        if ($search !== '') {
            foreach ($groups as &$group) {
                $group['products'] = array_values(array_filter(
                    $group['products'],
                    static fn (array $product): bool => mb_stripos((string) $product['title'], $search) !== false
                        || mb_stripos((string) $product['product_code'], $search) !== false
                        || mb_stripos((string) ($product['composition'] ?? ''), $search) !== false
                ));
            }
            unset($group);
            $groups = array_values(array_filter($groups, static fn (array $group): bool => $group['products'] !== []));
        }
        if ($activeCategory !== null) {
            $groups = array_values(array_filter(
                $groups,
                static fn (array $group): bool => $group['id'] === $activeCategory
            ));
        }

        View::render('home', [
            'title' => 'Wholesale fabric catalog',
            'allGroups' => $allGroups,
            'groups' => $groups,
            'activeCategory' => $activeCategory,
            'search' => $search,
            'unavailable' => $unavailable,
            'cartQuantities' => (new CartService())->raw(),
        ]);
    }
}
