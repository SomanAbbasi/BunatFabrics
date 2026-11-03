<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Global helpers
|--------------------------------------------------------------------------
| Small, intention-revealing functions available everywhere. Classes own
| behaviour; these only remove noise from controllers and views.
*/

if (! function_exists('base_path')) {
    /**
     * Absolute path inside the project root.
     */
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }
}

if (! function_exists('config')) {
    /**
     * Read a config value using dot notation, e.g. config('database.host').
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $files = [];

        $segments = explode('.', $key);
        $file     = array_shift($segments);

        if (! array_key_exists($file, $files)) {
            $path        = base_path("config/{$file}.php");
            $files[$file] = is_file($path) ? require $path : [];
        }

        $value = $files[$file];

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

if (! function_exists('e')) {
    /**
     * Escape a value for safe output in HTML.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('old')) {
    /**
     * Re-fill a form field with the previous submission.
     */
    function old(string $key, string $default = ''): string
    {
        return App\Core\Session::old($key, $default);
    }
}

if (! function_exists('pkr')) {
    /**
     * Format a amount using the wholesale catalog currency.
     */
    function pkr(mixed $amount): string
    {
        return 'PKR ' . number_format((float) $amount, 2);
    }
}

if (! function_exists('url')) {
    /**
     * Build an absolute URL from an application path.
     */
    function url(string $path = ''): string
    {
        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}

if (! function_exists('redirect')) {
    /**
     * Send the visitor to another path and stop execution.
     */
    function redirect(string $path): never
    {
        $target = str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : url($path);

        header('Location: ' . $target);
        exit;
    }
}
