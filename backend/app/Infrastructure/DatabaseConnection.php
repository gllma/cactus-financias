<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;

class DatabaseConnection
{
    public static function make(): PDO
    {
        $host = self::envOr('DB_HOST', 'db');
        $port = self::envOr('DB_PORT', '5432');
        $database = self::envOr('DB_DATABASE', 'cactus_financias');
        $username = self::envOr('DB_USERNAME', 'cactus');
        $password = self::envOr('DB_PASSWORD', 'cactus');

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database);

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private static function envOr(string $key, string $default): string
    {
        $value = getenv($key);

        if ($value === false || trim($value) === '') {
            return $default;
        }

        return $value;
    }
}
