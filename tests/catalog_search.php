<?php

declare(strict_types=1);

require __DIR__ . '/../src/Home/CatalogSearch.php';

use App\Home\CatalogSearch;

function checkSearch(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
    echo "PASS: {$message}\n";
}

$groups = [
    ['name' => 'Lawn', 'products' => [
        ['title' => 'Summer cloth', 'product_code' => 'FAB-123', 'composition' => 'Cotton'],
        ['title' => 'Cotton blend', 'product_code' => 'FAB-1234', 'composition' => null],
    ]],
    ['name' => 'Denim', 'products' => [
        ['title' => 'Heavy cloth', 'product_code' => 'DEN-1', 'composition' => 'Cotton'],
    ]],
];

checkSearch(CatalogSearch::query(['bad']) === '', 'array input is ignored');
checkSearch(CatalogSearch::query("\xFF") === '', 'invalid UTF-8 is ignored');
checkSearch(mb_strlen(CatalogSearch::query(str_repeat('a', 120))) === 100, 'query length is bounded');
checkSearch(count(CatalogSearch::filter($groups, 'cotton lawn')[0]['products']) === 2, 'terms match across category and product fields');
checkSearch(CatalogSearch::filter($groups, 'DEN-1')[0]['name'] === 'Denim', 'exact code works');
checkSearch(CatalogSearch::filter($groups, '<script>') === [], 'special characters are treated as text');
checkSearch(count(CatalogSearch::filter($groups, 'cotton')) === 2, 'matching covers categories');
checkSearch(CatalogSearch::filter($groups, '') === $groups, 'empty search retains catalog');
