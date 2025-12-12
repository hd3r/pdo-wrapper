<?php

declare(strict_types=1);

namespace Hd3r\PdoWrapper\Driver;

use Hd3r\PdoWrapper\Exception\ConnectionException;
use PDO;
use PDOException;

/**
 * SQLite database driver.
 *
 * Connects to SQLite databases using PDO.
 * Defaults to in-memory database if no path specified.
 */
class SqliteDriver extends AbstractDriver
{
    /**
     * Create a SQLite database connection.
     *
     * @param string|null $path Path to SQLite file, ':memory:' for in-memory, or null
     *                          Falls back to DB_SQLITE_PATH env var, then ':memory:'
     *
     * @throws ConnectionException When connection fails
     */
    public function __construct(?string $path = null)
    {
        $envPath = $_ENV['DB_SQLITE_PATH'] ?? null;
        $path = $path ?? ($envPath !== null ? (string)$envPath : ':memory:');

        $dsn = sprintf('sqlite:%s', $path);

        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, null, null, $defaultOptions);
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        } catch (PDOException $e) {
            throw new ConnectionException(
                message: 'Database connection failed',
                code: (int)$e->getCode(),
                previous: $e,
                debugMessage: sprintf('SQLite connection failed: %s', $e->getMessage())
            );
        }
    }
}
