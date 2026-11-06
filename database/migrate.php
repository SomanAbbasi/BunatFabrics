<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Initial database setup
|--------------------------------------------------------------------------
| Usage:  php database/migrate.php
|
|  1. Boots the app so .env + config are available.
|  2. Executes database/schema.sql (idempotent).
|  3. Leaves all existing accounts unchanged.
|  4. Creates the admin account if missing from ADMIN_EMAIL / ADMIN_PASSWORD in .env,
|     storing the password as a bcrypt hash.
*/

require __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Core\Env;

echo "Bunat Fabrics — database migration\n";
echo str_repeat('-', 40) . "\n";

$pdo = Database::connection();

runSqlFile($pdo, __DIR__ . '/schema.sql', 'schema');
// Existing accounts are never seeded or overwritten by setup.

syncAdmin($pdo);

$total = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "Done. users table now holds {$total} row(s).\n";

/**
 * Execute every statement in a .sql file.
 */
function runSqlFile(PDO $pdo, string $path, string $label): void
{
    if (! is_file($path)) {
        exit("Missing {$label} file: {$path}\n");
    }

    $sql = (string) file_get_contents($path);
    $pdo->exec($sql);

    echo "  [ok] applied {$label}.sql\n";
}

/**
 * Create the configured administrator only if that email is not already in use.
 */
function syncAdmin(PDO $pdo): void
{
    $name     = Env::get('ADMIN_NAME', 'Site Admin');
    $email    = strtolower(trim((string) Env::get('ADMIN_EMAIL', '')));
    $password = (string) Env::get('ADMIN_PASSWORD', '');

    if ($email === '' || $password === '') {
        echo "  [--] ADMIN_EMAIL / ADMIN_PASSWORD not set, skipped admin setup\n";

        return;
    }

    if ((int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0) {
        echo "  [--] administrator already exists, skipped setup\n";
        return;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $statement = $pdo->prepare(
        'INSERT INTO users (name, email, password, role)
         VALUES (:name, :email, :password, :role)
         ON DUPLICATE KEY UPDATE id = id'
    );

    $statement->execute([
        'name'     => $name,
        'email'    => $email,
        'password' => $hash,
        'role'     => 'admin',
    ]);

    echo "  [ok] admin setup checked from .env ({$email})\n";
}
