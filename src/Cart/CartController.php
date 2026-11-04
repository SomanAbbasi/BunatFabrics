<?php

declare(strict_types=1);

namespace App\Cart;

use App\Auth\Guard;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Orders\OrderService;
use Throwable;

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

    public function checkout(): void
    {
        $user = $this->requireCustomer();

        $items = $this->cart->items();

        if ($items === []) {
            Session::flash('error', 'Your cart is empty. Add a fabric before checking out.');

            redirect('/cart');
        }

        View::render('cart/checkout', [
            'title'      => 'Checkout',
            'user'       => $user,
            'items'      => $items,
            'subtotal'   => $this->cart->subtotal(),
            'unitTotals' => $this->cart->unitTotals(),
            'itemCount'  => $this->cart->count(),
            'errors'     => (array) Session::pullFlash('errors', []),
        ]);
    }

    public function placeOrder(): void
    {
        $user = $this->requireCustomer();

        $this->requireCsrf('/checkout');

        $items = $this->cart->items();

        if ($items === []) {
            Session::flash('error', 'Your cart is empty. Add a fabric before checking out.');

            redirect('/cart');
        }

        $contact = [
            'phone'   => trim((string) ($_POST['phone'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
        ];

        $errors = (new Validator($contact))->validate(
            [
                'phone'   => 'required|phone',
                'address' => 'required|min:10|max:500',
            ],
            [
                'phone'   => 'Phone number',
                'address' => 'Delivery address',
            ],
        );

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flashInput($contact);

            redirect('/checkout');
        }

        try {
            $orderId = (new OrderService())->place((int) $user['id'], $items, $this->cart->subtotal(), $contact);
        } catch (Throwable $exception) {
            error_log('Order placement failed: ' . $exception->getMessage());
            Session::flash('error', 'We could not place your order. Please try again.');

            redirect('/checkout');
        }

        $this->cart->clear();
        Session::flash('status', 'Your order has been placed.');

        redirect('/checkout/success?order=' . $orderId);
    }

    public function success(): void
    {
        $user = $this->requireCustomer();

        $orderId = filter_var($_GET['order'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $result  = $orderId === false ? null : (new OrderService())->findForUser((int) $orderId, (int) $user['id']);

        if ($result === null) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Order not found']);

            return;
        }

        View::render('cart/success', [
            'title' => 'Order confirmed',
            'user'  => $user,
            'order' => $result['order'],
            'items' => $result['items'],
        ]);
    }

    /**
     * Checkout is only available to signed in customers.
     *
     * The current path is remembered so the shopper returns here after login
     * or signup; the session cart is kept intact by the auth flow.
     */
    private function requireCustomer(): array
    {
        $user = Guard::user();

        if ($user === null) {
            Session::setIntendedUrl('/checkout');
            Session::flash('error', 'Please sign in or create an account to continue to checkout.');

            redirect('/login');
        }

        return $user;
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
