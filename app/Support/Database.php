<?php

namespace App\Support;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST'),
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME')
            );

            $maxAttempts = 5;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    self::$connection = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'), [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    break;
                } catch (PDOException $e) {
                    if ($attempt === $maxAttempts) {
                        // ERR-01: never expose connection/credential details to the user
                        error_log('DB connection failed after ' . $maxAttempts . ' attempts: ' . $e->getMessage());
                        throw new PDOException('Database connection failed.');
                    }

                    sleep(1);
                }
            }
        }

        return self::$connection;
    }
}
