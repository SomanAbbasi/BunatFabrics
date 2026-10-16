<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Very small router: registers GET/POST paths and dispatches them to
 * [Controller::class, 'method'] handlers.
 */
final class Router
{
    /** @var array<string, array<string, array{0: class-string, 1: string}>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function get(string $uri, array $action): void
    {
        $this->routes['GET'][$this->normalize($uri)] = $action;
    }

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function post(string $uri, array $action): void
    {
        $this->routes['POST'][$this->normalize($uri)] = $action;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri    = $this->normalize($uri);

        $action = $this->routes[$method][$uri] ?? null;

        if ($action === null) {
            $this->abort(404);

            return;
        }

        [$controller, $methodName] = $action;

        try {
            (new $controller())->{$methodName}();
        } catch (Throwable $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Reduce a URI to a comparable form: no query string, no trailing slash.
     */
    private function normalize(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }

    private function abort(int $status): void
    {
        http_response_code($status);

        View::render('errors/404', [
            'title' => 'Page not found',
        ]);
    }

    private function handleException(Throwable $exception): void
    {
        http_response_code(500);

        if ((bool) config('app.debug')) {
            echo '<pre>' . htmlspecialchars((string) $exception, ENT_QUOTES) . '</pre>';

            return;
        }

        View::render('errors/500', [
            'title' => 'Something went wrong',
        ]);
    }
}
