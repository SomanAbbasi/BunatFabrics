<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper around PHP sessions: values, one-time flash messages and CSRF
 * tokens used by the forms.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Store a value to be read (and removed) on the very next request.
     */
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pullFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;

        unset($_SESSION['_flash'][$key]);

        return $value;
    }

    /**
     * Remember the current input so it can be re-filled after a failed submit.
     */
    public static function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirmation']);

        self::put('_old_input', $input);
    }

    public static function old(string $key, string $default = ''): string
    {
        return (string) ($_SESSION['_old_input'][$key] ?? $default);
    }

    public static function clearOldInput(): void
    {
        self::forget('_old_input');
    }

    /**
     * Remember where to send the visitor after a successful sign in.
     */
    public static function setIntendedUrl(string $path): void
    {
        self::put('intended_url', $path);
    }

    /**
     * Read and clear the intended path, rejecting anything off-site.
     */
    public static function pullIntendedUrl(string $default = '/'): string
    {
        $path = self::get('intended_url');

        self::forget('intended_url');

        if (! is_string($path) || $path === '' || $path[0] !== '/' || str_starts_with($path, '//')) {
            return $default;
        }

        return $path;
    }

    public static function csrfToken(): string
    {
        if (! self::has('_csrf_token')) {
            self::put('_csrf_token', bin2hex(random_bytes(32)));
        }

        return (string) self::get('_csrf_token');
    }

    public static function verifyCsrf(?string $token): bool
    {
        $stored = self::get('_csrf_token');

        return is_string($stored) && is_string($token) && hash_equals($stored, $token);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
