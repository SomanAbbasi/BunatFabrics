<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/** Additive, versioned migrations; never runs the legacy account seeds. */
final class Migrator
{
    public static function run(PDO $pdo, string $directory): array
    {
        $database = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        if ($database === '') {
            throw new RuntimeException('Select a database before migrating.');
        }
        $lock = 'bunat_migrate_' . substr(hash('sha256', $database), 0, 40);
        $statement = $pdo->prepare('SELECT GET_LOCK(?, 10)');
        $statement->execute([$lock]);
        if ((int) $statement->fetchColumn() !== 1) {
            throw new RuntimeException('Another migration is running.');
        }
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(190) NOT NULL PRIMARY KEY,
                checksum CHAR(64) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $files = glob($directory . '/*.sql');
            if ($files === false || $files === []) {
                throw new RuntimeException('No migration files found.');
            }
            sort($files, SORT_STRING);
            $applied = [];
            foreach ($files as $file) {
                $version = basename($file);
                $sql = file_get_contents($file);
                if ($sql === false) {
                    throw new RuntimeException('Cannot read migration: ' . $version);
                }
                $checksum = hash('sha256', $sql);
                $statement = $pdo->prepare('SELECT checksum FROM schema_migrations WHERE version = ?');
                $statement->execute([$version]);
                $existing = $statement->fetchColumn();
                if ($existing !== false) {
                    if (! hash_equals($existing, $checksum)) {
                        throw new RuntimeException('Applied migration changed: ' . $version);
                    }
                    continue;
                }
                // MySQL DDL commits implicitly. Each file is one repeatable CREATE TABLE.
                $pdo->exec($sql);
                $statement = $pdo->prepare('INSERT INTO schema_migrations (version, checksum) VALUES (?, ?)');
                $statement->execute([$version, $checksum]);
                $applied[] = $version;
            }
            return $applied;
        } finally {
            $statement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $statement->execute([$lock]);
        }
    }
}
