<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Minimal, dependency-free .env reader.
 *
 * Keeps configuration out of the code base: every value is loaded once at
 * boot and can be fetched with Env::get('KEY').
 */
final class Env
{
    /** @var array<string, string> */
    private static array $items = [];

    private static bool $loaded = false;

    /**
     * Parse a .env file and merge its values into the environment.
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (! is_file($path)) {
            throw new RuntimeException("Environment file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            if (! str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key   = trim($key);
            $value = self::normalize(trim($value));

            self::$items[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * Read a value, falling back to the given default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $default;
    }

    /**
     * Read a value coerced to boolean (true/1/yes/on).
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::$items[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Strip surrounding quotes and unescape common sequences.
     */
    private static function normalize(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return stripcslashes(substr($value, 1, -1));
            }
        }

        return $value;
    }
}
