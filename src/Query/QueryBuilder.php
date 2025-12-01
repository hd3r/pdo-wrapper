<?php

declare(strict_types=1);

namespace PdoWrapper\Query;

use PDO;
use PdoWrapper\DatabaseInterface;
use PdoWrapper\Exception\QueryException;

/**
 * Fluent query builder for constructing SQL queries.
 *
 * Supports SELECT, INSERT, UPDATE, DELETE with WHERE conditions,
 * JOINs, ORDER BY, GROUP BY, HAVING, LIMIT, and OFFSET.
 */
class QueryBuilder
{
    private DatabaseInterface $db;
    private string $table;
    private string $quoteChar;

    private array $columns = ['*'];
    private array $wheres = [];
    private array $joins = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $groupBy = [];
    private array $having = [];
    private bool $distinct = false;

    /**
     * Create a new query builder instance.
     *
     * @param DatabaseInterface $db Database connection
     * @param string $table Table name
     * @param string $quoteChar Quote character for identifiers (" or `)
     */
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
     * Usage:
     * - select('*')
     * - select('id, name')
     * - select(['id', 'name'])
     * - select(['users.id', 'users.name as username'])
     *
     * @param string|array $columns Column(s) to select
     * @return self
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

    /**
     * Add DISTINCT to the query.
     *
     * @return self
     */
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
     *
     * @param string|array $column Column name or array of conditions
     * @param mixed $operatorOrValue Operator or value (if 2 args)
     * @param mixed $value Value (if 3 args)
     * @return self
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

        return $this;
    }

    /**
     * Add a WHERE IN condition.
     *
     * @param string $column Column name
     * @param array $values Values to match
     * @return self
     * @throws QueryException When $values is empty
     */
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

        return $this;
    }

    /**
     * Add a WHERE NOT IN condition.
     *
     * @param string $column Column name
     * @param array $values Values to exclude
     * @return self
     * @throws QueryException When $values is empty
     */
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

        return $this;
    }

    /**
     * Add a WHERE BETWEEN condition.
     *
     * @param string $column Column name
     * @param array $values [min, max] values
     * @return self
     * @throws QueryException When $values doesn't have exactly 2 elements
     */
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

        return $this;
    }

    /**
     * Add a WHERE NOT BETWEEN condition.
     *
     * @param string $column Column name
     * @param array $values [min, max] values to exclude
     * @return self
     * @throws QueryException When $values doesn't have exactly 2 elements
     */
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

        return $this;
    }

    /**
     * Add a WHERE IS NULL condition.
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'not' => false,
        ];

        return $this;
    }

    /**
     * Add a WHERE IS NOT NULL condition.
     *
     * @param string $column Column name
     * @return self
     */
    public function whereNotNull(string $column): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'not' => true,
        ];

        return $this;
    }

    /**
     * Add a WHERE LIKE condition.
     *
     * @param string $column Column name
     * @param string $pattern LIKE pattern (use % for wildcards)
     * @return self
     */
    public function whereLike(string $column, string $pattern): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => 'LIKE',
            'value' => $pattern,
        ];

        return $this;
    }

    /**
     * Add a WHERE NOT LIKE condition.
     *
     * @param string $column Column name
     * @param string $pattern LIKE pattern to exclude
     * @return self
     */
    public function whereNotLike(string $column, string $pattern): self
    {
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => 'NOT LIKE',
            'value' => $pattern,
        ];

        return $this;
    }

    // =========================================================================
    // JOINS
    // =========================================================================

    /**
     * Add an INNER JOIN.
     *
     * @param string $table Table to join
     * @param string $first First column (left side)
     * @param string $operator Comparison operator (=, <, >, etc.)
     * @param string $second Second column (right side)
     * @return self
     */
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

    /**
     * Add a LEFT JOIN.
     *
     * @param string $table Table to join
     * @param string $first First column (left side)
     * @param string $operator Comparison operator
     * @param string $second Second column (right side)
     * @return self
     */
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

    /**
     * Add a RIGHT JOIN.
     *
     * @param string $table Table to join
     * @param string $first First column (left side)
     * @param string $operator Comparison operator
     * @param string $second Second column (right side)
     * @return self
     */
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

    /**
     * Add an ORDER BY clause.
     *
     * @param string $column Column to order by
     * @param string $direction ASC or DESC (default: ASC)
     * @return self
     */
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

    /**
     * Set the LIMIT clause.
     *
     * @param int $limit Maximum number of rows
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the OFFSET clause.
     *
     * @param int $offset Number of rows to skip
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // =========================================================================
    // GROUP BY, HAVING
    // =========================================================================

    /**
     * Add a GROUP BY clause.
     *
     * @param string|array $columns Column(s) to group by
     * @return self
     */
    public function groupBy(string|array $columns): self
    {
        if (is_string($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        $this->groupBy = array_merge($this->groupBy, $columns);

        return $this;
    }

    /**
     * Add a HAVING condition.
     *
     * Used with GROUP BY for aggregate conditions.
     *
     * @param string $column Column or aggregate function (e.g., 'COUNT(*)')
     * @param string $operator Comparison operator
     * @param mixed $value Value to compare
     * @return self
     */
    public function having(string $column, string $operator, mixed $value): self
    {
        $this->having[] = [
            'column' => $column,
            'operator' => strtoupper($operator),
            'value' => $value,
        ];

        return $this;
    }

    // =========================================================================
    // EXECUTE
    // =========================================================================

    /**
     * Execute the query and get all results.
     *
     * @return array Array of rows as associative arrays
     * @throws QueryException On query failure
     */
    public function get(): array
    {
        [$sql, $params] = $this->toSql();
        $stmt = $this->db->query($sql, $params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute the query and get the first result.
     *
     * @return array|null First row or null if none found
     * @throws QueryException On query failure
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * Check if any records exist matching the query.
     *
     * @return bool True if at least one record exists
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Get the count of matching records.
     *
     * @param string $column Column to count (default: *)
     * @return int Number of records
     */
    public function count(string $column = '*'): int
    {
        return (int)$this->aggregate('COUNT', $column);
    }

    /**
     * Get the sum of a column.
     *
     * @param string $column Column to sum
     * @return float|int|null Sum or null if no rows
     */
    public function sum(string $column): float|int|null
    {
        $result = $this->aggregate('SUM', $column);
        return $result !== null ? (float)$result : null;
    }

    /**
     * Get the average of a column.
     *
     * @param string $column Column to average
     * @return float|int|null Average or null if no rows
     */
    public function avg(string $column): float|int|null
    {
        $result = $this->aggregate('AVG', $column);
        return $result !== null ? (float)$result : null;
    }

    /**
     * Get the minimum value of a column.
     *
     * @param string $column Column to check
     * @return mixed Minimum value or null if no rows
     */
    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Get the maximum value of a column.
     *
     * @param string $column Column to check
     * @return mixed Maximum value or null if no rows
     */
    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Execute an aggregate function.
     *
     * @param string $function Aggregate function (COUNT, SUM, AVG, MIN, MAX)
     * @param string $column Column to aggregate
     * @return mixed Aggregate result or null
     */
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
     * Insert a row via the query builder.
     *
     * @param array $data Column => value pairs
     * @return int|string Last insert ID
     * @throws QueryException On failure
     */
    public function insert(array $data): int|string
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update rows matching the WHERE conditions.
     *
     * Requires at least one WHERE condition for safety.
     *
     * @param array $data Column => value pairs to update
     * @return int Number of affected rows
     * @throws QueryException When no WHERE conditions set (safety)
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
     * Delete rows matching the WHERE conditions.
     *
     * Requires at least one WHERE condition for safety.
     *
     * @return int Number of affected rows
     * @throws QueryException When no WHERE conditions set (safety)
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
     * Get the SQL query and parameters without executing.
     *
     * Useful for debugging or logging.
     *
     * @return array [sql, params]
     */
    public function toSql(): array
    {
        [$sql, $params] = $this->buildSelect();

        return [$sql, $params];
    }

    // =========================================================================
    // BUILDER METHODS
    // =========================================================================

    /**
     * Build the SELECT statement and collect params in correct SQL order.
     *
     * Parameters are collected in SQL clause order:
     * WHERE params first, then HAVING params.
     *
     * @return array [sql, params]
     */
    private function buildSelect(): array
    {
        $sql = 'SELECT ';
        $params = [];

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

        // WHERE - params collected in SQL order
        if (!empty($this->wheres)) {
            [$whereSql, $whereParams] = $this->buildWhere();
            $sql .= ' WHERE ' . $whereSql;
            $params = array_merge($params, $whereParams);
        }

        // GROUP BY
        if (!empty($this->groupBy)) {
            $quotedGroupBy = array_map([$this, 'quoteIdentifier'], $this->groupBy);
            $sql .= ' GROUP BY ' . implode(', ', $quotedGroupBy);
        }

        // HAVING - params collected AFTER where params (SQL order)
        if (!empty($this->having)) {
            [$havingSql, $havingParams] = $this->buildHaving();
            $sql .= ' HAVING ' . $havingSql;
            $params = array_merge($params, $havingParams);
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

        return [$sql, $params];
    }

    /**
     * Build WHERE clause and extract params.
     *
     * @return array [sql, params]
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

    /**
     * Build HAVING clause and extract params.
     *
     * Aggregate functions (containing parentheses) are not quoted.
     *
     * @return array [sql, params]
     */
    private function buildHaving(): array
    {
        $clauses = [];
        $params = [];

        foreach ($this->having as $h) {
            // Don't quote aggregate functions (they contain parentheses)
            $column = str_contains($h['column'], '(')
                ? $h['column']
                : $this->quoteIdentifier($h['column']);
            $clauses[] = $column . ' ' . $h['operator'] . ' ?';
            $params[] = $h['value'];
        }

        return [implode(' AND ', $clauses), $params];
    }

    /**
     * Quote an identifier (table/column name).
     *
     * Handles:
     * - Simple: "column" → "column"
     * - Dotted: "table.column" → "table"."column"
     * - Alias: "column as alias" → "column" as alias
     *
     * @param string $identifier Identifier to quote
     * @return string Quoted identifier
     */
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
