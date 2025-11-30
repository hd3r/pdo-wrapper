<?php

declare(strict_types=1);

namespace PdoWrapper\Driver;

use PDO;
use PDOException;
use PdoWrapper\Exception\ConnectionException;

class SqliteDriver
{
    private PDO $pdo;

    public function __construct(?string $path = null)
    {
        $path = $path ?? $_ENV['DB_SQLITE_PATH'] ?? ':memory:';

        $dsn = sprintf('sqlite:%s', $path);

        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, null, null, $defaultOptions);
        } catch (PDOException $e) {
            throw new ConnectionException(
                message: 'Database connection failed',
                code: (int)$e->getCode(),
                previous: $e,
                debugMessage: sprintf('SQLite connection failed: %s', $e->getMessage())
            );
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
