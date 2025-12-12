<?php

declare(strict_types=1);

namespace Hd3r\PdoWrapper\Driver;

use Hd3r\PdoWrapper\Exception\ConnectionException;
use PDO;
use PDOException;

/**
 * MySQL database driver.
 *
 * Connects to MySQL databases using PDO with utf8mb4 charset by default.
 * Supports configuration via array or environment variables.
 */
class MySqlDriver extends AbstractDriver
{
    /**
     * Create a MySQL database connection.
     *
     * Config keys:
     * - host: MySQL server hostname (required)
     * - database: Database name (required)
     * - username: Database username (required)
     * - password: Database password (optional)
     * - port: Server port (default: 3306)
     * - charset: Connection charset (default: utf8mb4)
     * - options: Additional PDO options
     *
     * Falls back to environment variables: DB_HOST, DB_DATABASE,
     * DB_USERNAME, DB_PASSWORD, DB_PORT
     *
     * @param array{host?: string, database?: string, username?: string, password?: string, port?: int, charset?: string, options?: array<int, mixed>} $config
     *
     * @throws ConnectionException When required config is missing or connection fails
     */
    public function __construct(array $config = [])
    {
        $host = $config['host'] ?? $_ENV['DB_HOST'] ?? null;
        $database = $config['database'] ?? $_ENV['DB_DATABASE'] ?? null;
        $username = $config['username'] ?? $_ENV['DB_USERNAME'] ?? null;
        $password = $config['password'] ?? $_ENV['DB_PASSWORD'] ?? null;
        $envPort = $_ENV['DB_PORT'] ?? null;
        $port = $config['port'] ?? (is_numeric($envPort) ? (int)$envPort : 3306);
        $charset = $config['charset'] ?? 'utf8mb4';

        if ($host === null || $database === null || $username === null) {
            throw new ConnectionException(
                message: 'Database connection failed',
                debugMessage: 'Missing required config: host, database, or username'
            );
        }

        // Type assertions after null check
        $host = (string)$host;
        $database = (string)$database;
        $username = (string)$username;
        $password = $password !== null ? (string)$password : null;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            $port,
            $database,
            $charset
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
                debugMessage: sprintf('MySQL connection to %s:%d failed: %s', $host, $port, $e->getMessage())
            );
        }
    }

    /**
     * Quote an identifier using MySQL backticks.
     *
     * Handles database.table and table.column format:
     * - `users` → `users`
     * - `mydb.users` → `mydb`.`users`
     *
     * @param string $identifier Identifier to quote
     *
     * @return string Quoted identifier
     */
    protected function quoteIdentifier(string $identifier): string
    {
        // Handle schema.table or table.column format
        if (str_contains($identifier, '.')) {
            $parts = explode('.', $identifier);
            return implode('.', array_map(
                fn ($part) => '`' . str_replace('`', '``', $part) . '`',
                $parts
            ));
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * Get the MySQL quote character (backtick).
     */
    protected function getQuoteChar(): string
    {
        return '`';
    }
}
