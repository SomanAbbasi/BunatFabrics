<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Renders a view file inside the shared layout.
 *
 * The view is captured into the $content variable that views/layout.php
 * expects, so pages stay focused on their own markup.
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = []): void
    {
        $path = BASE_PATH . '/views/' . str_replace('.', '/', $view) . '.php';

        if (! is_file($path)) {
            throw new RuntimeException("View not found: {$view}");
        }

        $title = (string) ($data['title'] ?? config('app.name'));

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;
        $content = (string) ob_get_clean();

        require BASE_PATH . '/views/layout.php';

        Session::clearOldInput();
    }
}
