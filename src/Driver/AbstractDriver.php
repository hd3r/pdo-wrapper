<?php

declare(strict_types=1);

namespace PdoWrapper\Driver;

use Closure;
use PDO;
use PDOException;
use PDOStatement;
use PdoWrapper\DatabaseInterface;
use PdoWrapper\Exception\QueryException;
use PdoWrapper\Exception\TransactionException;
use PdoWrapper\Traits\HasHooks;
use Throwable;

abstract class AbstractDriver implements DatabaseInterface
{
    use HasHooks;

    protected PDO $pdo;

    public function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $this->trigger('query', [
                'sql' => $sql,
                'params' => $params,
                'duration' => microtime(true) - $start,
                'rows' => $stmt->rowCount(),
            ]);

            return $stmt;
        } catch (PDOException $e) {
            $this->trigger('error', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new QueryException(
                message: 'Query failed',
                code: (int)$e->getCode(),
                previous: $e,
                debugMessage: sprintf('%s | SQL: %s', $e->getMessage(), $sql)
            );
        }
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return $this->pdo->lastInsertId($name);
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->trigger('transaction.begin', []);
        } catch (PDOException $e) {
            throw new TransactionException(
                message: 'Failed to begin transaction',
                code: (int)$e->getCode(),
                previous: $e,
                debugMessage: $e->getMessage()
            );
        }
    }

    public function commit(): void
    {
        try {
            $this->pdo->commit();
            $this->trigger('transaction.commit', []);
        } catch (PDOException $e) {
            throw new TransactionException(
                message: 'Failed to commit transaction',
                code: (int)$e->getCode(),
                previous: $e,
                debugMessage: $e->getMessage()
            );
        }
    }

    public function rollback(): void
    {
        try {
            $this->pdo->rollBack();
            $this->trigger('transaction.rollback', []);
        } catch (PDOException $e) {
            throw new TransactionException(
                message: 'Failed to rollback transaction',
                code: (int)$e->getCode(),
                previous: $e,
                debugMessage: $e->getMessage()
            );
        }
    }

    public function transaction(Closure $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // =========================================================================
    // CRUD Helper
    // =========================================================================

    public function insert(string $table, array $data): int|string
    {
        if (empty($data)) {
            throw new QueryException(
                message: 'Insert failed',
                debugMessage: 'Cannot insert empty data'
            );
        }

        $columns = array_keys($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(', ', array_map([$this, 'quoteIdentifier'], $columns)),
            implode(', ', $placeholders)
        );

        $this->query($sql, array_values($data));

        return $this->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        if (empty($data)) {
            throw new QueryException(
                message: 'Update failed',
                debugMessage: 'Cannot update with empty data'
            );
        }

        if (empty($where)) {
            throw new QueryException(
                message: 'Update failed',
                debugMessage: 'Cannot update without WHERE conditions (safety check)'
            );
        }

        $setClauses = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setClauses[] = $this->quoteIdentifier($column) . ' = ?';
            $params[] = $value;
        }

        [$whereSql, $whereParams] = $this->buildWhereClause($where);
        $params = array_merge($params, $whereParams);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteIdentifier($table),
            implode(', ', $setClauses),
            $whereSql
        );

        return $this->execute($sql, $params);
    }

    public function delete(string $table, array $where): int
    {
        if (empty($where)) {
            throw new QueryException(
                message: 'Delete failed',
                debugMessage: 'Cannot delete without WHERE conditions (safety check)'
            );
        }

        [$whereSql, $params] = $this->buildWhereClause($where);

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $this->quoteIdentifier($table),
            $whereSql
        );

        return $this->execute($sql, $params);
    }

    public function findOne(string $table, array $where): ?array
    {
        [$whereSql, $params] = $this->buildWhereClause($where);

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s LIMIT 1',
            $this->quoteIdentifier($table),
            $whereSql
        );

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    public function findAll(string $table, array $where = []): array
    {
        if (empty($where)) {
            $sql = sprintf('SELECT * FROM %s', $this->quoteIdentifier($table));
            $params = [];
        } else {
            [$whereSql, $params] = $this->buildWhereClause($where);
            $sql = sprintf(
                'SELECT * FROM %s WHERE %s',
                $this->quoteIdentifier($table),
                $whereSql
            );
        }

        $stmt = $this->query($sql, $params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateMultiple(string $table, array $rows, string $keyColumn = 'id'): int
    {
        if (empty($rows)) {
            return 0;
        }

        $affected = 0;

        foreach ($rows as $row) {
            if (!isset($row[$keyColumn])) {
                throw new QueryException(
                    message: 'Update failed',
                    debugMessage: sprintf('Missing key column "%s" in row', $keyColumn)
                );
            }

            $keyValue = $row[$keyColumn];
            $data = array_diff_key($row, [$keyColumn => null]);

            if (!empty($data)) {
                $affected += $this->update($table, $data, [$keyColumn => $keyValue]);
            }
        }

        return $affected;
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Quote an identifier (table/column name).
     * Override in driver for DB-specific quoting.
     */
    protected function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Build WHERE clause from conditions array.
     *
     * @param array $where Column => value pairs
     * @return array [sql, params]
     */
    protected function buildWhereClause(array $where): array
    {
        $clauses = [];
        $params = [];

        foreach ($where as $column => $value) {
            $clauses[] = $this->quoteIdentifier($column) . ' = ?';
            $params[] = $value;
        }

        return [implode(' AND ', $clauses), $params];
    }
}
