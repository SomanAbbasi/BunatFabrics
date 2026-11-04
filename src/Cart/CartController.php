<?php

declare(strict_types=1);

namespace App\Cart;

use App\Core\Session;
use App\Core\View;

/**
 * Shopping cart screens and actions.
 */
final class CartController
{
    private CartService $cart;

    public function __construct(?CartService $cart = null)
    {
        $this->cart = $cart ?? new CartService();
    }

    public function index(): void
    {
        View::render('cart/index', [
            'title'      => 'Your cart',
            'items'      => $this->cart->items(),
            'subtotal'   => $this->cart->subtotal(),
            'unitTotals' => $this->cart->unitTotals(),
            'itemCount'  => $this->cart->count(),
        ]);
    }

    public function add(): void
    {
        $this->requireCsrf('/cart');

        $productId = $this->postInt('product_id');
        $quantity  = $this->postInt('quantity', 1);

        if ($productId === null || $productId < 1) {
            Session::flash('error', 'Please choose a fabric to add to your cart.');
            redirect('/');
        }

        $result = $this->cart->add($productId, $quantity ?? 1);

        Session::flash($result['ok'] ? 'status' : 'error', $result['message']);

        redirect($result['ok'] ? '/cart' : '/');
    }

    public function update(): void
    {
        $this->requireCsrf('/cart');

        $productId = $this->postInt('product_id');
        $quantity  = $this->postInt('quantity', 0) ?? 0;

        if ($productId !== null && $productId > 0) {
            $this->cart->update($productId, $quantity);
        }

        Session::flash('status', 'Your cart has been updated.');

        redirect('/cart');
    }

    public function remove(): void
    {
        $this->requireCsrf('/cart');

        $productId = $this->postInt('product_id');

        if ($productId !== null && $productId > 0) {
            $this->cart->remove($productId);
        }

        Session::flash('status', 'Item removed from your cart.');

        redirect('/cart');
    }

    private function requireCsrf(string $fallback): void
    {
        if (! Session::verifyCsrf($_POST['_token'] ?? null)) {
            Session::flash('error', 'Your session expired. Please try again.');

            redirect($fallback);
        }
    }

    private function postInt(string $key, ?int $default = null): ?int
    {
        $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? $default : $value;
    }
}
