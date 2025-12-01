<?php

declare(strict_types=1);

namespace PdoWrapper\Query;

use PDO;
use PdoWrapper\DatabaseInterface;
use PdoWrapper\Exception\QueryException;

class QueryBuilder
{
    private DatabaseInterface $db;
    private string $table;
    private string $quoteChar;

    private array $columns = ['*'];
    private array $wheres = [];
    private array $params = [];
    private array $joins = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $groupBy = [];
    private array $having = [];
    private bool $distinct = false;

    public function __construct(DatabaseInterface $db, string $table, string $quoteChar = '"')
    {
        $this->db = $db;
        $this->table = $table;
        $this->quoteChar = $quoteChar;
    }

    // =========================================================================
    // SELECT
    // =========================================================================

    /**
     * Set columns to select.
     *
     * @param string|array $columns
     */
    public function select(string|array $columns = '*'): self
    {
        if ($columns === '*') {
            $this->columns = ['*'];
        } elseif (is_string($columns)) {
            $this->columns = array_map('trim', explode(',', $columns));
        } else {
            $this->columns = $columns;
        }

        return $this;
    }

    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    // =========================================================================
    // WHERE
    // =========================================================================

    /**
     * Add a WHERE condition.
     *
     * Usage:
     * - where('id', 5)           → id = 5
     * - where('id', '=', 5)      → id = 5
     * - where('age', '>', 18)    → age > 18
     * - where(['active' => 1])   → active = 1
     */
    public function where(string|array $column, mixed $operatorOrValue = null, mixed $value = null): self
    {
        // Array syntax: where(['active' => 1, 'role' => 'admin'])
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->where($col, '=', $val);
            }
            return $this;
        }

        // Two params: where('id', 5) → equals
        if ($value === null && $operatorOrValue !== null) {
            $value = $operatorOrValue;
            $operatorOrValue = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper($operatorOrValue),
            'value' => $value,
        ];
        $this->params[] = $value;

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            throw new QueryException(
                message: 'Query failed',
                debugMessage: 'whereIn requires a non-empty array'
            );
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'not' => false,
        ];
        $this->params = array_merge($this->params, $values);

        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        if (empty($values)) {
            throw new QueryException(
                message: 'Query failed',
                debugMessage: 'whereNotIn requires a non-empty array'
            );
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'not' => true,
        ];
        $this->params = array_merge($this->params, $values);

        return $this;
    }

    public function whereBetween(string $column, array $values): self
    {
        if (count($values) !== 2) {
            throw new QueryException(
                message: 'Query failed',
                debugMessage: 'whereBetween requires exactly 2 values'
            );
        }

        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'not' => false,
        ];
        $this->params[] = $values[0];
        $this->params[] = $values[1];

        return $this;
    }

    public function whereNotBetween(string $column, array $values): self
    {
        if (count($values) !== 2) {
            throw new QueryException(
                message: 'Query failed',
                debugMessage: 'whereNotBetween requires exactly 2 values'
            );
        }

        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'not' => true,
        ];
        $this->params[] = $values[0];
        $this->params[] = $values[1];

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'not' => false,
        ];

        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'not' => true,
        ];

        return $this;
    }

    public function whereLike(string $column, string $pattern): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => 'LIKE',
            'value' => $pattern,
        ];
        $this->params[] = $pattern;

        return $this;
    }

    public function whereNotLike(string $column, string $pattern): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => 'NOT LIKE',
            'value' => $pattern,
        ];
        $this->params[] = $pattern;

        return $this;
    }

    // =========================================================================
    // JOINS
    // =========================================================================

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    public function rightJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'RIGHT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    // =========================================================================
    // ORDER BY, LIMIT, OFFSET
    // =========================================================================

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            $direction = 'ASC';
        }

        $this->orderBy[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // =========================================================================
    // GROUP BY, HAVING
    // =========================================================================

    public function groupBy(string|array $columns): self
    {
        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $this->groupBy = array_merge($this->groupBy, $columns);

        return $this;
    }

    public function having(string $column, string $operator, mixed $value): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
        ];
        $this->params[] = $value;

        return $this;
    }

    // =========================================================================
    // EXECUTE
    // =========================================================================

    /**
     * Execute and get all results.
     */
    public function get(): array
    {
        [$sql, $params] = $this->toSql();
        $stmt = $this->db->query($sql, $params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute and get the first result.
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * Check if any records exist.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Get the count of records.
     */
    public function count(string $column = '*'): int
    {
        return (int)$this->aggregate('COUNT', $column);
    }

    public function sum(string $column): float|int|null
    {
        $result = $this->aggregate('SUM', $column);
        return $result !== null ? (float)$result : null;
    }

    public function avg(string $column): float|int|null
    {
        $result = $this->aggregate('AVG', $column);
        return $result !== null ? (float)$result : null;
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    private function aggregate(string $function, string $column): mixed
    {
        $originalColumns = $this->columns;

        if ($column === '*') {
            $this->columns = ["{$function}(*) as aggregate"];
        } else {
            $this->columns = ["{$function}({$this->quoteIdentifier($column)}) as aggregate"];
        }

        [$sql, $params] = $this->toSql();
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->columns = $originalColumns;

        return $result['aggregate'] ?? null;
    }

    // =========================================================================
    // INSERT, UPDATE, DELETE
    // =========================================================================

    /**
     * Insert a row via query builder.
     */
    public function insert(array $data): int|string
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update rows matching WHERE conditions.
     */
    public function update(array $data): int
    {
        if (empty($this->wheres)) {
            throw new QueryException(
                message: 'Update failed',
                debugMessage: 'Cannot update without WHERE conditions (safety check). Use updateAll() to update all rows.'
            );
        }

        [$whereSql, $whereParams] = $this->buildWhere();

        $setClauses = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setClauses[] = $this->quoteIdentifier($column) . ' = ?';
            $params[] = $value;
        }

        $params = array_merge($params, $whereParams);

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s',
            $this->quoteIdentifier($this->table),
            implode(', ', $setClauses),
            $whereSql
        );

        return $this->db->execute($sql, $params);
    }

    /**
     * Delete rows matching WHERE conditions.
     */
    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new QueryException(
                message: 'Delete failed',
                debugMessage: 'Cannot delete without WHERE conditions (safety check). Use deleteAll() to delete all rows.'
            );
        }

        [$whereSql, $whereParams] = $this->buildWhere();

        $sql = sprintf(
            'DELETE FROM %s WHERE %s',
            $this->quoteIdentifier($this->table),
            $whereSql
        );

        return $this->db->execute($sql, $whereParams);
    }

    // =========================================================================
    // DEBUG
    // =========================================================================

    /**
     * Get the SQL and params without executing.
     *
     * @return array [sql, params]
     */
    public function toSql(): array
    {
        $sql = $this->buildSelect();

        return [$sql, $this->params];
    }

    // =========================================================================
    // BUILDER METHODS
    // =========================================================================

    private function buildSelect(): string
    {
        $sql = 'SELECT ';

        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }

        // Columns
        if ($this->columns === ['*']) {
            $sql .= '*';
        } else {
            $quotedColumns = array_map(function ($col) {
                // Don't quote aggregate functions or *
                if (str_contains($col, '(') || $col === '*') {
                    return $col;
                }
                return $this->quoteIdentifier($col);
            }, $this->columns);
            $sql .= implode(', ', $quotedColumns);
        }

        // FROM
        $sql .= ' FROM ' . $this->quoteIdentifier($this->table);

        // JOINS
        foreach ($this->joins as $join) {
            $sql .= sprintf(
                ' %s JOIN %s ON %s %s %s',
                $join['type'],
                $this->quoteIdentifier($join['table']),
                $this->quoteIdentifier($join['first']),
                $join['operator'],
                $this->quoteIdentifier($join['second'])
            );
        }

        // WHERE
        if (!empty($this->wheres)) {
            [$whereSql] = $this->buildWhere();
            $sql .= ' WHERE ' . $whereSql;
        }

        // GROUP BY
        if (!empty($this->groupBy)) {
            $quotedGroupBy = array_map([$this, 'quoteIdentifier'], $this->groupBy);
            $sql .= ' GROUP BY ' . implode(', ', $quotedGroupBy);
        }

        // HAVING
        if (!empty($this->having)) {
            $havingClauses = [];
            foreach ($this->having as $h) {
                $havingClauses[] = $this->quoteIdentifier($h['column']) . ' ' . $h['operator'] . ' ?';
            }
            $sql .= ' HAVING ' . implode(' AND ', $havingClauses);
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $orderClauses = [];
            foreach ($this->orderBy as $order) {
                $orderClauses[] = $this->quoteIdentifier($order['column']) . ' ' . $order['direction'];
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }

        // LIMIT (cast to int for security)
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . (int)$this->limit;
        }

        // OFFSET (cast to int for security)
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . (int)$this->offset;
        }

        return $sql;
    }

    /**
     * Build WHERE clause (without params, they're already in $this->params).
     *
     * @return array [sql, params for this where only]
     */
    private function buildWhere(): array
    {
        $clauses = [];
        $params = [];

        foreach ($this->wheres as $where) {
            switch ($where['type']) {
                case 'basic':
                    $clauses[] = $this->quoteIdentifier($where['column']) . ' ' . $where['operator'] . ' ?';
                    $params[] = $where['value'];
                    break;

                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $operator = $where['not'] ? 'NOT IN' : 'IN';
                    $clauses[] = $this->quoteIdentifier($where['column']) . " {$operator} ({$placeholders})";
                    $params = array_merge($params, $where['values']);
                    break;

                case 'between':
                    $operator = $where['not'] ? 'NOT BETWEEN' : 'BETWEEN';
                    $clauses[] = $this->quoteIdentifier($where['column']) . " {$operator} ? AND ?";
                    $params[] = $where['values'][0];
                    $params[] = $where['values'][1];
                    break;

                case 'null':
                    $operator = $where['not'] ? 'IS NOT NULL' : 'IS NULL';
                    $clauses[] = $this->quoteIdentifier($where['column']) . ' ' . $operator;
                    break;
            }
        }

        return [implode(' AND ', $clauses), $params];
    }

    private function quoteIdentifier(string $identifier): string
    {
        // Handle alias: "column as alias" or "table.column as alias"
        if (preg_match('/^(.+)\s+as\s+(\w+)$/i', $identifier, $matches)) {
            return $this->quoteIdentifier(trim($matches[1])) . ' as ' . $matches[2];
        }

        // Handle table.column format
        if (str_contains($identifier, '.')) {
            $parts = explode('.', $identifier);
            return implode('.', array_map(fn($p) => $this->quoteChar . $p . $this->quoteChar, $parts));
        }

        return $this->quoteChar . $identifier . $this->quoteChar;
    }
}
