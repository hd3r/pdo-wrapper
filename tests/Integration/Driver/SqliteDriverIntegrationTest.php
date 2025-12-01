<?php

declare(strict_types=1);

namespace Hd3r\PdoWrapper\Tests\Integration\Driver;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Hd3r\PdoWrapper\DatabaseInterface;
use Hd3r\PdoWrapper\Driver\SqliteDriver;
use Hd3r\PdoWrapper\Exception\ConnectionException;

class SqliteDriverIntegrationTest extends TestCase
{
    public function testImplementsDatabaseInterface(): void
    {
        $driver = new SqliteDriver();

        $this->assertInstanceOf(DatabaseInterface::class, $driver);
    }

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

    public function testQueryReturnsStatement(): void
    {
        $driver = new SqliteDriver();

        $stmt = $driver->query('SELECT 1 as test');

        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertSame(1, $stmt->fetch()['test']);
    }

    public function testExecuteReturnsAffectedRows(): void
    {
        $driver = new SqliteDriver();
        $driver->execute('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');

        $affected = $driver->execute("INSERT INTO test (name) VALUES (?)", ['hello']);

        $this->assertSame(1, $affected);
    }

    public function testLastInsertId(): void
    {
        $driver = new SqliteDriver();
        $driver->execute('CREATE TABLE test (id INTEGER PRIMARY KEY, name TEXT)');
        $driver->execute("INSERT INTO test (name) VALUES (?)", ['hello']);

        $id = $driver->lastInsertId();

        $this->assertSame('1', $id);
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

        $driver = new SqliteDriver(':memory:');

        $this->assertInstanceOf(PDO::class, $driver->getPdo());

        unset($_ENV['DB_SQLITE_PATH']);
    }

    public function testInvalidPathThrowsConnectionException(): void
    {
        $this->expectException(ConnectionException::class);

        new SqliteDriver('/nonexistent/directory/that/does/not/exist/test.db');
    }
}
