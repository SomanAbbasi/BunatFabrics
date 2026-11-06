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

if (! function_exists('icon')) {
    /**
     * Render one of the small inline stroke icons used across the UI.
     */
    function icon(string $name, string $class = 'icon'): string
    {
        $paths = [
            'grid'         => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
            'cart'         => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2l2.4 11.2a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 2-1.6L21 7H5"/>',
            'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
            'alert-circle' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16h.01"/>',
            'user'         => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
            'user-plus'    => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M19 8v6"/><path d="M16 11h6"/>',
            'phone'        => '<path d="M6.5 3h3l1.5 4-2 1.5a12 12 0 0 0 6.5 6.5L17 13l4 1.5v3a2 2 0 0 1-2.2 2A17.5 17.5 0 0 1 4.5 5.2 2 2 0 0 1 6.5 3Z"/>',
            'map-pin'      => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
            'plus'         => '<path d="M12 5v14"/><path d="M5 12h14"/>',
            'arrow-right'  => '<path d="M4 12h16"/><path d="m14 6 6 6-6 6"/>',
            'package'      => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4 7.5 8 4.5 8-4.5"/><path d="M12 12v9"/>',
            'trash'        => '<path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7l1 13h10l1-13"/><path d="M10 11v5"/><path d="M14 11v5"/>',
            'lock'         => '<rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
            'fabric'       => '<path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Z"/><path d="M4 10h16"/><path d="M4 14h16"/><path d="M10 4v16"/><path d="M14 4v16"/>',
            'layers'       => '<polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>',
            'sparkles'     => '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3L12 3z"/>',
        ];

        if (! isset($paths[$name])) {
            return '';
        }

        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
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
