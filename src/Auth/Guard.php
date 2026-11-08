<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Core\View;

/**
 * Session based authentication guard.
 *
 * Only the user id is stored in the session; the record is reloaded on demand
 * so account details are always fresh.
 */
final class Guard
{
    private const SESSION_KEY = 'auth_id';

    private static ?array $cachedUser = null;

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return is_int($id) ? $id : (is_numeric($id) ? (int) $id : null);
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $id = self::id();

        if ($id === null) {
            return null;
        }

        $user = (new AuthService())->find($id);
        $stamp = Session::get('auth_stamp');
        if (!$user || !is_string($stamp) || !hash_equals($stamp, AuthSecurity::stamp($user))) {
            self::logout();
            return null;
        }
        return self::$cachedUser = $user;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? null) === 'admin';
    }

    /** Require a current administrator before reading or changing admin data. */
    public static function requireAdmin(): array
    {
        $user = self::user();

        if ($user === null) {
            self::logout();
            redirect('/login');
        }

        if (! self::isAdmin()) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Page not found']);
            exit;
        }

        return $user;
    }

    public static function login(array $user): void
    {
        Session::forget('google_verified');
        Session::regenerate();
        Session::put(self::SESSION_KEY, (int) $user['id']);
        Session::put('auth_stamp', AuthSecurity::stamp($user));

        self::$cachedUser = $user;
    }

    public static function logout(): void
    {
        self::$cachedUser = null;

        Session::forget(self::SESSION_KEY);
        Session::forget('auth_stamp');
        Session::forget('google_verified');
        Session::forget('google_flow');
    }
}
