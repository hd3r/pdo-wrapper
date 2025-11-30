<?php

declare(strict_types=1);

namespace PdoWrapper;

use Closure;
use PDO;
use PDOStatement;

interface DatabaseInterface
{
    /**
     * Execute a SQL query and return the statement.
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement
     * @throws Exception\QueryException
     */
    public function query(string $sql, array $params = []): PDOStatement;

    /**
     * Execute a SQL statement and return affected rows.
     *
     * @param string $sql SQL statement with placeholders
     * @param array $params Parameters to bind
     * @return int Number of affected rows
     * @throws Exception\QueryException
     */
    public function execute(string $sql, array $params = []): int;

    /**
     * Get the last inserted ID.
     */
    public function lastInsertId(?string $name = null): string|false;

    /**
     * Get the underlying PDO instance.
     */
    public function getPdo(): PDO;

    /**
     * Begin a transaction.
     *
     * @throws Exception\TransactionException
     */
    public function beginTransaction(): void;

    /**
     * Commit the current transaction.
     *
     * @throws Exception\TransactionException
     */
    public function commit(): void;

    /**
     * Roll back the current transaction.
     *
     * @throws Exception\TransactionException
     */
    public function rollback(): void;

    /**
     * Execute a callback within a transaction.
     * Auto-commits on success, auto-rollback on exception.
     *
     * @param Closure $callback Receives the driver instance
     * @return mixed Return value of the callback
     * @throws \Throwable Re-throws any exception after rollback
     */
    public function transaction(Closure $callback): mixed;

    /**
     * Register a hook callback for an event.
     *
     * Events: 'query', 'error', 'transaction.begin', 'transaction.commit', 'transaction.rollback'
     *
     * @param string $event Event name
     * @param Closure $callback Callback receiving event data array
     */
    public function on(string $event, Closure $callback): void;
}
