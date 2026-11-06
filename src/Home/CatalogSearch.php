<?php

declare(strict_types=1);

namespace App\Home;

final class CatalogSearch
{
    public static function query(mixed $value): string
    {
        if (! is_string($value) || ! mb_check_encoding($value, 'UTF-8')) {
            return '';
        }

        return mb_substr(trim($value), 0, 100, 'UTF-8');
    }

    public static function filter(array $groups, string $query): array
    {
        if ($query === '') {
            return $groups;
        }

        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($groups as &$group) {
            $group['products'] = array_values(array_filter($group['products'], static function (array $product) use ($terms, $group): bool {
                $fields = [$product['title'], $product['product_code'], $product['composition'] ?? '', $group['name']];
                foreach ($terms as $term) {
                    foreach ($fields as $field) {
                        if (mb_stripos((string) $field, $term, 0, 'UTF-8') !== false) {
                            continue 2;
                        }
                    }
                    return false;
                }
                return true;
            }));
        }
        unset($group);

        $groups = array_values(array_filter($groups, static fn (array $group): bool => $group['products'] !== []));
        // Keep category sections intact while putting an exact product code first.
        foreach ($groups as &$group) {
            foreach ($group['products'] as $index => $product) {
                if (mb_strtolower((string) $product['product_code'], 'UTF-8') === mb_strtolower($query, 'UTF-8')) {
                    array_splice($group['products'], $index, 1);
                    array_unshift($group['products'], $product);
                    break;
                }
            }
        }
        unset($group);
        usort($groups, static function (array $a, array $b) use ($query): int {
            $aExact = mb_strtolower((string) $a['products'][0]['product_code'], 'UTF-8') === mb_strtolower($query, 'UTF-8');
            $bExact = mb_strtolower((string) $b['products'][0]['product_code'], 'UTF-8') === mb_strtolower($query, 'UTF-8');
            return (int) $bExact <=> (int) $aExact;
        });

        return $groups;
    }
}
