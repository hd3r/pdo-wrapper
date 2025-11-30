<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Integration\Driver;

use PDO;
use PHPUnit\Framework\TestCase;
use PdoWrapper\Driver\SqliteDriver;

class SqliteDriverIntegrationTest extends TestCase
{
    public function testConnectsToMemoryDatabase(): void
    {
        $driver = new SqliteDriver();

        $this->assertInstanceOf(PDO::class, $driver->getPdo());
    }

    public function testConnectsWithExplicitMemoryPath(): void
    {
        $driver = new SqliteDriver(':memory:');

        $this->assertInstanceOf(PDO::class, $driver->getPdo());
    }

    public function testCanExecuteQuery(): void
    {
        $driver = new SqliteDriver();
        $pdo = $driver->getPdo();

        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();

        $this->assertSame(1, $result['test']);
    }

    public function testCanCreateTableAndInsert(): void
    {
        $driver = new SqliteDriver();
        $pdo = $driver->getPdo();

        $pdo->exec('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec("INSERT INTO test (name) VALUES ('hello')");

        $stmt = $pdo->query('SELECT * FROM test');
        $result = $stmt->fetch();

        $this->assertSame(1, $result['id']);
        $this->assertSame('hello', $result['name']);
    }

    public function testConnectionUsesExceptionErrorMode(): void
    {
        $driver = new SqliteDriver();
        $pdo = $driver->getPdo();

        $errorMode = $pdo->getAttribute(PDO::ATTR_ERRMODE);

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $errorMode);
    }

    public function testConnectionUsesFetchAssoc(): void
    {
        $driver = new SqliteDriver();
        $pdo = $driver->getPdo();

        $fetchMode = $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->assertSame(PDO::FETCH_ASSOC, $fetchMode);
    }

    public function testReadsPathFromEnv(): void
    {
        $_ENV['DB_SQLITE_PATH'] = ':memory:';

        $driver = new SqliteDriver();

        $this->assertInstanceOf(PDO::class, $driver->getPdo());

        unset($_ENV['DB_SQLITE_PATH']);
    }

    public function testExplicitPathOverridesEnv(): void
    {
        $_ENV['DB_SQLITE_PATH'] = '/some/other/path.db';

        // Explicit :memory: should override ENV
        $driver = new SqliteDriver(':memory:');

        $this->assertInstanceOf(PDO::class, $driver->getPdo());

        unset($_ENV['DB_SQLITE_PATH']);
    }
}
