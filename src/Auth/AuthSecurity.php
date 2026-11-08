<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;
use App\Core\Validator;
use PDO;

final class AuthSecurity
{
    public static function input(string $key, ?array $source = null): string
    {
        $source ??= $_POST;
        return is_string($source[$key] ?? null) ? $source[$key] : '';
    }

    public static function csrf(): void
    {
        if (!Session::verifyCsrf(self::input('_token'))) {
            http_response_code(403);
            exit('Your session expired. Reload the page and try again.');
        }
    }

    public static function privateResponse(): void
    {
        header('Cache-Control: no-store');
        header('Referrer-Policy: no-referrer');
    }

    public static function stamp(array $user): string
    {
        return hash('sha256', $user['id'] . ':' . $user['email'] . ':' . ($user['password'] ?? ''));
    }

    public static function passwordErrors(string $password, string $confirmation): array
    {
        $errors = (new Validator(['password' => $password]))->validate(['password' => 'required|password']);
        if (strlen($password) > 72 || str_contains($password, "\0") || !mb_check_encoding($password, 'UTF-8')) {
            $errors['password'] = 'Choose a shorter password without unsupported characters.';
        }
        if ($password !== $confirmation || $confirmation === '') {
            $errors['password_confirmation'] = 'Enter the same password in both fields.';
        }
        return $errors;
    }

    // Atomic fixed-window limit shared by all PHP workers; no trusted proxy headers.
    public static function allow(PDO $pdo, string $key, int $maximum, int $seconds): bool
    {
        $bucket = hash('sha256', $key);
        $statement = $pdo->prepare('INSERT INTO auth_limits (bucket,hits,expires_at) VALUES (?,1,DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND)) ON DUPLICATE KEY UPDATE hits=IF(expires_at<=UTC_TIMESTAMP(),1,hits+1), expires_at=IF(expires_at<=UTC_TIMESTAMP(),VALUES(expires_at),expires_at)');
        $statement->execute([$bucket, $seconds]);
        $statement = $pdo->prepare('SELECT hits FROM auth_limits WHERE bucket=?');
        $statement->execute([$bucket]);
        return (int) $statement->fetchColumn() <= $maximum;
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');
    }
}
