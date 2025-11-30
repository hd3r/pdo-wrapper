<?php

declare(strict_types=1);

namespace PdoWrapper\Driver;

use PDO;
use PDOException;
use PdoWrapper\Exception\ConnectionException;

class MySqlDriver extends AbstractDriver
{
    /**
     * @param array{host?: string, database?: string, username?: string, password?: string, port?: int, charset?: string, options?: array} $config
     */
    public function __construct(array $config = [])
    {
        $host = $config['host'] ?? $_ENV['DB_HOST'] ?? null;
        $database = $config['database'] ?? $_ENV['DB_DATABASE'] ?? null;
        $username = $config['username'] ?? $_ENV['DB_USERNAME'] ?? null;
        $password = $config['password'] ?? $_ENV['DB_PASSWORD'] ?? null;
        $port = $config['port'] ?? (isset($_ENV['DB_PORT']) ? (int)$_ENV['DB_PORT'] : 3306);
        $charset = $config['charset'] ?? 'utf8mb4';

        if ($host === null || $database === null || $username === null) {
            throw new ConnectionException(
                message: 'Database connection failed',
                debugMessage: 'Missing required config: host, database, or username'
            );
        }

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
}
