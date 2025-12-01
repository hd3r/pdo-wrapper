<?php

declare(strict_types=1);

namespace PdoWrapper\Driver;

use PDO;
use PDOException;
use PdoWrapper\Exception\ConnectionException;

/**
 * PostgreSQL database driver.
 *
 * Connects to PostgreSQL databases using PDO.
 * Supports configuration via array or environment variables.
 */
class PostgresDriver extends AbstractDriver
{
    /**
     * Create a PostgreSQL database connection.
     *
     * Config keys:
     * - host: PostgreSQL server hostname (required)
     * - database: Database name (required)
     * - username: Database username (required)
     * - password: Database password (optional)
     * - port: Server port (default: 5432)
     * - options: Additional PDO options
     *
     * Falls back to environment variables: DB_HOST, DB_DATABASE,
     * DB_USERNAME, DB_PASSWORD, DB_PORT
     *
     * @param array{host?: string, database?: string, username?: string, password?: string, port?: int, options?: array} $config
     * @throws ConnectionException When required config is missing or connection fails
     */
    public function __construct(array $config = [])
    {
        $host = $config['host'] ?? $_ENV['DB_HOST'] ?? null;
        $database = $config['database'] ?? $_ENV['DB_DATABASE'] ?? null;
        $username = $config['username'] ?? $_ENV['DB_USERNAME'] ?? null;
        $password = $config['password'] ?? $_ENV['DB_PASSWORD'] ?? null;
        $port = $config['port'] ?? (isset($_ENV['DB_PORT']) ? (int)$_ENV['DB_PORT'] : 5432);

        if ($host === null || $database === null || $username === null) {
            throw new ConnectionException(
                message: 'Database connection failed',
                debugMessage: 'Missing required config: host, database, or username'
            );
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $host,
            $port,
            $database
        );

        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $options = array_replace($defaultOptions, $config['options'] ?? []);

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new ConnectionException(
                message: 'Database connection failed',
                code: (int)$e->getCode(),
                previous: $e,
                debugMessage: sprintf('PostgreSQL connection to %s:%d failed: %s', $host, $port, $e->getMessage())
            );
        }
    }
}
