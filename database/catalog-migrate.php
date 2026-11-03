<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Core\Migrator;

if (! in_array(config('app.env'), ['local', 'development', 'testing'], true)) {
    fwrite(STDERR, "Catalog migrations are restricted to development/testing environments.\n");
    exit(1);
}
try {
    $pdo = Database::connection();
    echo 'Target database: ', $pdo->query('SELECT DATABASE()')->fetchColumn(), PHP_EOL;
    $applied = Migrator::run($pdo, __DIR__ . '/migrations');
    echo $applied === [] ? "Already up to date.\n" : 'Applied: ' . implode(', ', $applied) . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, "Migration failed. No account seeds were run. Check database permissions and migration definitions.\n");
    exit(1);
}
