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
}
