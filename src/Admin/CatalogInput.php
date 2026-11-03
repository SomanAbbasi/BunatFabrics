<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Session;
use App\Core\View;

/** Shared input boundary for the two catalog features. */
final class CatalogInput
{
    public static function text(array $data, string $key, string $default = ''): string
    {
        return isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : $default;
    }

    public static function id(mixed $value): ?int
    {
        if (! is_scalar($value) || ! preg_match('/^[1-9][0-9]{0,9}$/D', (string) $value)) {
            return null;
        }
        $id = (int) $value;
        return $id <= 4294967295 ? $id : null;
    }

    public static function page(): int
    {
        return min(1000000, self::id($_GET['page'] ?? null) ?? 1);
    }

    public static function csrf(): void
    {
        $token = $_POST['_token'] ?? null;
        if (! is_string($token) || ! Session::verifyCsrf($token)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Invalid form token', 'message' => 'Your form expired. Go back, reload the page, and try again.']);
            exit;
        }
    }

    public static function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Not found']);
        exit;
    }
}
