# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.1] - 2025-12-13

### Fixed
- PostgreSQL `insert()` now uses `RETURNING id` for reliable ID retrieval instead of `lastInsertId()`.
  - Fixes issue where `lastInsertId()` returns empty string without explicit sequence name.
  - Note: Assumes primary key column is named `id`. For other PK names, use raw query with `RETURNING`.

## [1.2.0] - 2025-12-12

### Changed
- **BREAKING**: Environment variable handling moved from drivers to `Database` factory class.
  - Drivers now require explicit config array (no default ENV fallback).
  - Use `Database::mysql()`, `Database::postgres()`, `Database::sqlite()` for ENV support.
  - *No external users affected - library has no dependents yet.*
- Added `getenv()` fallback for legacy compatibility (`$_ENV` still takes priority for thread-safety).

### Fixed
- `first()` no longer mutates QueryBuilder state (now uses clone internally).
- SQLite foreign key constraints are now enabled by default (`PRAGMA foreign_keys = ON`).
- `updateMultiple()` now wraps operations in a transaction for atomicity.
- Aggregate methods (`count()`, `sum()`, `avg()`, `min()`, `max()`) now ignore `limit()`, `offset()`, and `orderBy()` for correct totals and PostgreSQL compatibility.
- `insert()` now throws `QueryException` when `lastInsertId()` returns false.
- Transaction rollback failures no longer mask the original exception.

### Added
- PHPStan static analysis at level 9 (maximum strictness).
- PHP-CS-Fixer for PSR-12 code style enforcement.
- GitHub Actions CI pipeline:
  - PHP 8.1, 8.2, 8.3, 8.4 (SQLite tests)
  - MySQL 8.0
  - MariaDB 10.11, 11.4
  - PostgreSQL 15, 16
- `@group mysql` and `@group postgres` annotations for database-specific tests.
- Environment variable configuration for test databases.

## [1.1.0] - 2024-12-11

### Fixed
- Handle missing parameters in debug messages and exceptions.

### Added
- MariaDB to keywords and documentation.
- Real-world transaction example in README.

## [1.0.0] - 2024-12-11

### Added
- Initial release.
- **Connection layer** with MySQL, PostgreSQL, and SQLite drivers.
  - Configuration via array or environment variables (`$_ENV`).
  - PDO options with sensible defaults (exceptions, associative fetch).
- **Query execution** with `query()` and `execute()` methods.
  - Prepared statements with parameter binding.
  - Query duration tracking.
- **Transaction support** with `beginTransaction()`, `commit()`, `rollback()`.
  - `transaction()` helper with auto-commit/rollback.
- **Event hooks** for query logging and error handling.
  - Events: `query`, `error`, `transaction.begin`, `transaction.commit`, `transaction.rollback`.
- **CRUD helper methods**:
  - `insert()` - Insert row and return last insert ID.
  - `update()` - Update rows with WHERE conditions (safety check).
  - `delete()` - Delete rows with WHERE conditions (safety check).
  - `findOne()` - Find single row by conditions.
  - `findAll()` - Find all rows matching conditions.
  - `updateMultiple()` - Batch update by key column.
- **Fluent QueryBuilder** with full SQL support:
  - `select()`, `distinct()`
  - `where()`, `whereIn()`, `whereNotIn()`, `whereBetween()`, `whereNotBetween()`
  - `whereNull()`, `whereNotNull()`, `whereLike()`, `whereNotLike()`
  - `join()`, `leftJoin()`, `rightJoin()`
  - `orderBy()`, `limit()`, `offset()`
  - `groupBy()`, `having()`
  - `get()`, `first()`, `exists()`
  - `count()`, `sum()`, `avg()`, `min()`, `max()`
  - `insert()`, `update()`, `delete()`
  - `toSql()` for debugging.
- **`Database::raw()`** for explicit raw SQL expressions (aggregates, complex queries).
- **Security hardening**:
  - Operator whitelist validation.
  - Proper identifier escaping (prevents SQL injection).
- **Exception hierarchy** for granular error handling:
  - `DatabaseException` (base)
  - `ConnectionException`
  - `QueryException`
  - `TransactionException`

[Unreleased]: https://github.com/hd3r/pdo-wrapper/compare/v1.2.1...HEAD
[1.2.1]: https://github.com/hd3r/pdo-wrapper/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/hd3r/pdo-wrapper/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/hd3r/pdo-wrapper/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/hd3r/pdo-wrapper/releases/tag/v1.0.0
