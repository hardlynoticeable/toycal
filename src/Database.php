<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $conn = null;

    public static function getConnection(): PDO
    {
        // If a test database connection exists, use it.
        if (isset($GLOBALS['test_pdo'])) {
            return $GLOBALS['test_pdo'];
        }

        if (self::$conn === null) {
            $config = require __DIR__ . '/config.php';
            $dbConfig = $config['db'];

            $dsn = '';
            $user = null;
            $pass = null;

            if ($dbConfig['driver'] === 'mysql') {
                $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
                $user = $dbConfig['user'];
                $pass = $dbConfig['pass'];
            } elseif ($dbConfig['driver'] === 'sqlite') {
                $dsn = "sqlite:{$dbConfig['path']}";
            }

            try {
                self::$conn = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                throw new PDOException($e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$conn;
    }
}
