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

    // =========================================================================
    // CRUD Helper
    // =========================================================================

    /**
     * Insert a row and return the last insert ID.
     *
     * @param string $table Table name
     * @param array $data Column => value pairs
     * @return int|string Last insert ID
     * @throws Exception\QueryException
     */
    public function insert(string $table, array $data): int|string;

    /**
     * Update rows matching WHERE conditions.
     *
     * @param string $table Table name
     * @param array $data Column => value pairs to update
     * @param array $where WHERE conditions (column => value)
     * @return int Number of affected rows
     * @throws Exception\QueryException When $where is empty (safety)
     */
    public function update(string $table, array $data, array $where): int;

    /**
     * Delete rows matching WHERE conditions.
     *
     * @param string $table Table name
     * @param array $where WHERE conditions (column => value)
     * @return int Number of affected rows
     * @throws Exception\QueryException When $where is empty (safety)
     */
    public function delete(string $table, array $where): int;

    /**
     * Find a single row by WHERE conditions.
     *
     * @param string $table Table name
     * @param array $where WHERE conditions (column => value)
     * @return array|null Row as associative array or null
     * @throws Exception\QueryException
     */
    public function findOne(string $table, array $where): ?array;

    /**
     * Find all rows matching WHERE conditions.
     *
     * @param string $table Table name
     * @param array $where WHERE conditions (optional)
     * @return array Array of rows
     * @throws Exception\QueryException
     */
    public function findAll(string $table, array $where = []): array;

    /**
     * Update multiple rows by their key column.
     *
     * @param string $table Table name
     * @param array $rows Array of rows with key column
     * @param string $keyColumn Column to match rows (default: 'id')
     * @return int Number of affected rows
     * @throws Exception\QueryException
     */
    public function updateMultiple(string $table, array $rows, string $keyColumn = 'id'): int;
}
