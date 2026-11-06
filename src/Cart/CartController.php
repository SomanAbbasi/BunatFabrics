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

    /**
     * JSON cart summary for client-side mini cart drawer.
     */
    public function data(): void
    {
        $this->jsonResponse([
            'ok'   => true,
            'cart' => $this->cart->summary(),
        ]);
    }

    public function add(): void
    {
        $this->requireCsrf('/cart');

        $productId = $this->postInt('product_id');
        $quantity  = $this->postInt('quantity', 1);

        if ($productId === null || $productId < 1) {
            if ($this->isJson()) {
                $this->jsonResponse(['ok' => false, 'message' => 'Please choose a fabric to add to your cart.'], 422);
            }
            Session::flash('error', 'Please choose a fabric to add to your cart.');
            redirect($this->safeReturnUrl($_POST['return_to'] ?? '/'));
        }

        $result = $this->cart->add($productId, $quantity ?? 1);

        if ($this->isJson()) {
            $this->jsonResponse([
                'ok'      => $result['ok'],
                'message' => $result['message'],
                'cart'    => $this->cart->summary(),
            ], $result['ok'] ? 200 : 422);
        }

        Session::flash($result['ok'] ? 'status' : 'error', $result['message']);

        // Instead of forcing the customer to the cart page, keep them on their current page
        // so they can effortlessly continue browsing and add more fabrics.
        $returnTo = $this->safeReturnUrl($_POST['return_to'] ?? $_SERVER['HTTP_REFERER'] ?? '/');
        redirect($returnTo);
    }

    public function update(): void
    {
        $this->requireCsrf('/cart');

        $productId = $this->postInt('product_id');
        $quantity  = $this->postInt('quantity', 0) ?? 0;

        if ($productId !== null && $productId > 0) {
            $this->cart->update($productId, $quantity);
        }

        if ($this->isJson()) {
            $this->jsonResponse([
                'ok'      => true,
                'message' => 'Your cart has been updated.',
                'cart'    => $this->cart->summary(),
            ]);
        }

        Session::flash('status', 'Your cart has been updated.');

        $returnTo = $this->safeReturnUrl($_POST['return_to'] ?? '/cart');
        redirect($returnTo);
    }

    public function remove(): void
    {
        $this->requireCsrf('/cart');

        $productId = $this->postInt('product_id');

        if ($productId !== null && $productId > 0) {
            $this->cart->remove($productId);
        }

        if ($this->isJson()) {
            $this->jsonResponse([
                'ok'      => true,
                'message' => 'Item removed from your cart.',
                'cart'    => $this->cart->summary(),
            ]);
        }

        Session::flash('status', 'Item removed from your cart.');

        $returnTo = $this->safeReturnUrl($_POST['return_to'] ?? '/cart');
        redirect($returnTo);
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
            'phone' => is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '',
            'address' => is_string($_POST['address'] ?? null) ? trim($_POST['address']) : '',
            'city' => is_string($_POST['city'] ?? null) ? trim($_POST['city']) : '',
            'payment_method' => is_string($_POST['payment_method'] ?? null) ? $_POST['payment_method'] : '',
        ];

        $errors = (new Validator($contact))->validate(
            [
                'phone'   => 'required|phone',
                'address' => 'required|min:10|max:500',
                'city' => 'required|max:80',
            ],
            [
                'phone'   => 'Phone number',
                'address' => 'Delivery address',
            ],
        );

        if (! in_array($contact['payment_method'], ['bank', 'cod'], true)) {
            $errors['payment_method'] = 'Choose a payment method.';
        }

        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flashInput($contact);

            redirect('/checkout');
        }

        try {
            $orderId = (new OrderService())->place((int) $user['id'], $items, $contact);
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

        if (($user['role'] ?? null) !== 'user') {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Customer access required', 'message' => 'Please sign in with a customer account to place an order.']);
            exit;
        }

        return $user;
    }

    private function requireCsrf(string $fallback): void
    {
        if (! is_string($_POST['_token'] ?? null) || ! Session::verifyCsrf($_POST['_token'])) {
            Session::flash('error', 'Your session expired. Please try again.');

            redirect($fallback);
        }
    }

    private function postInt(string $key, ?int $default = null): ?int
    {
        $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? $default : $value;
    }

    private function safeReturnUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '/';
        }

        $url = trim($url);
        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $query = parse_url($url, PHP_URL_QUERY);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $host = parse_url($url, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
            if ($host !== $appHost && $host !== 'localhost' && $host !== '127.0.0.1') {
                return '/';
            }
        }

        return $path . ($query ? '?' . $query : '');
    }

    private function isJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
    }

    private function jsonResponse(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
