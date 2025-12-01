<?php

declare(strict_types=1);

namespace Hd3r\PdoWrapper;

use Hd3r\PdoWrapper\Driver\MySqlDriver;
use Hd3r\PdoWrapper\Driver\PostgresDriver;
use Hd3r\PdoWrapper\Driver\SqliteDriver;

/**
 * Factory class for creating database connections.
 *
 * Usage:
 * - Database::mysql(['host' => '...', 'database' => '...', ...])
 * - Database::postgres(['host' => '...', 'database' => '...', ...])
 * - Database::sqlite(':memory:')
 */
class Database
{
    /**
     * Create a MySQL database connection.
     *
     * @param array{host?: string, database?: string, username?: string, password?: string, port?: int, charset?: string, options?: array} $config
     * @return MySqlDriver
     * @throws Exception\ConnectionException When connection fails
     */
    public static function mysql(array $config = []): MySqlDriver
    {
        return new MySqlDriver($config);
    }

    /**
     * Create a PostgreSQL database connection.
     *
     * @param array{host?: string, database?: string, username?: string, password?: string, port?: int, options?: array} $config
     * @return PostgresDriver
     * @throws Exception\ConnectionException When connection fails
     */
    public static function postgres(array $config = []): PostgresDriver
    {
        return new PostgresDriver($config);
    }

    /**
     * Create a SQLite database connection.
     *
     * @param string|null $path Path to SQLite file, ':memory:' for in-memory, or null for default
     * @return SqliteDriver
     * @throws Exception\ConnectionException When connection fails
     */
    public static function sqlite(?string $path = null): SqliteDriver
    {
        return new SqliteDriver($path);
    }
}
