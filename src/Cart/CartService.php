<?php

declare(strict_types=1);

namespace App\Cart;

use App\Core\Session;
use App\Home\CatalogService;

/**
 * Session backed shopping cart.
 *
 * Only product ids and quantities are stored in the session. Product details,
 * prices and availability are reloaded from the catalog on every request so a
 * cart can survive sign in without trusting stale data.
 */
final class CartService
{
    private const SESSION_KEY = 'cart';

    public const MAX_QUANTITY = 1000000;

    private CatalogService $catalog;

    public function __construct(?CatalogService $catalog = null)
    {
        $this->catalog = $catalog ?? new CatalogService();
    }

    /**
     * @return array<int, int> product id => quantity
     */
    public function raw(): array
    {
        $cart = Session::get(self::SESSION_KEY, []);

        if (! is_array($cart)) {
            return [];
        }

        $clean = [];
        foreach ($cart as $productId => $quantity) {
            $productId = (int) $productId;
            $quantity  = (int) $quantity;

            if ($productId > 0 && $quantity > 0) {
                $clean[$productId] = min($quantity, self::MAX_QUANTITY);
            }
        }

        return $clean;
    }

    /**
     * Add a product or increase its quantity when it is already in the cart.
     *
     * @return array{ok: bool, message: string}
     */
    public function add(int $productId, int $quantity): array
    {
        if ($this->catalog->findPublished($productId) === null) {
            return ['ok' => false, 'message' => 'That fabric is no longer available.'];
        }

        $quantity = max(1, min($quantity, self::MAX_QUANTITY));
        $cart     = $this->raw();
        $cart[$productId] = min(($cart[$productId] ?? 0) + $quantity, self::MAX_QUANTITY);

        $this->store($cart);

        return ['ok' => true, 'message' => 'Fabric added to your cart.'];
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->raw();

        if (! isset($cart[$productId])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = min($quantity, self::MAX_QUANTITY);
        }

        $this->store($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        $this->store($cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /**
     * Cart lines enriched with current product details and totals.
     *
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        $cart = $this->raw();

        if ($cart === []) {
            return [];
        }

        $products = $this->catalog->findPublishedMany(array_keys($cart));
        $items    = [];

        foreach ($cart as $productId => $quantity) {
            $product = $products[$productId] ?? null;

            if ($product === null) {
                unset($cart[$productId]);

                continue;
            }

            $price = (float) $product['price_pkr'];

            $product['quantity']   = $quantity;
            $product['line_total'] = round($price * $quantity, 2);
            $items[]               = $product;
        }

        // Drop products that disappeared from the published catalog.
        if (count($cart) !== count($items)) {
            $this->store($cart);
        }

        return $items;
    }

    public function subtotal(): float
    {
        $subtotal = 0.0;

        foreach ($this->items() as $item) {
            $subtotal += (float) $item['line_total'];
        }

        return round($subtotal, 2);
    }

    /**
     * Total quantity grouped by the product unit (meter/yard).
     *
     * @return array<string, int>
     */
    public function unitTotals(): array
    {
        $totals = [];

        foreach ($this->items() as $item) {
            $unit          = (string) ($item['unit'] ?? 'meter');
            $totals[$unit] = ($totals[$unit] ?? 0) + (int) $item['quantity'];
        }

        return $totals;
    }

    /**
     * Summary payload for JSON cart responses and drawer rendering.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $items = $this->items();
        $subtotal = $this->subtotal();

        $enrichedItems = array_map(static function (array $item): array {
            $imagePath = (string) ($item['image_path'] ?? '');
            $item['image_url'] = $imagePath !== '' ? \App\Admin\Products\ProductImageService::url($imagePath) : null;
            $item['price_formatted'] = pkr((float) $item['price_pkr']);
            $item['line_total_formatted'] = pkr((float) $item['line_total']);
            return $item;
        }, $items);

        return [
            'items'              => $enrichedItems,
            'count'              => $this->count(),
            'subtotal'           => $subtotal,
            'subtotal_formatted' => pkr($subtotal),
            'unitTotals'         => $this->unitTotals(),
            'isEmpty'            => empty($items),
        ];
    }

    /**
     * @param array<int, int> $cart
     */
    private function store(array $cart): void
    {
        if ($cart === []) {
            Session::forget(self::SESSION_KEY);

            return;
        }

        Session::put(self::SESSION_KEY, $cart);
    }
}
