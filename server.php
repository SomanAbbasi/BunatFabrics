<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Local development server router
|--------------------------------------------------------------------------
| Usage: php -S localhost:8080 -t public server.php
|
| Serves existing assets from public/ and sends every other request to the
| front controller so clean URLs work without Apache.
*/

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$root = realpath(__DIR__ . '/public');
$file = realpath($root . $path);

if ($root !== false && $path !== '/' && $file !== false && is_file($file) && str_starts_with($file, $root)) {
    return false;
}

require __DIR__ . '/public/index.php';
