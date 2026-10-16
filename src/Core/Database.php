<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Single shared PDO connection built from config/database.php.
 */
final class Database
{
    private static ?PDO $connection = null;

    private function __construct()
    {
    }

    /**
     * Return the shared connection, opening it on first use.
     */
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = (array) config('database');

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset'],
        );

        try {
            self::$connection = new PDO(
                $dsn,
                (string) $config['username'],
                (string) $config['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ],
            );
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'Unable to connect to the database: ' . $exception->getMessage(),
                (int) $exception->getCode(),
                $exception,
            );
        }

        return self::$connection;
    }
}
