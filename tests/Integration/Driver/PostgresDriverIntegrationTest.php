<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Integration\Driver;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PdoWrapper\DatabaseInterface;
use PdoWrapper\Driver\PostgresDriver;
use PdoWrapper\Exception\ConnectionException;

class PostgresDriverIntegrationTest extends TestCase
{
    private static array $config = [
        'host' => '127.0.0.1',
        'database' => 'pdo_wrapper_test',
        'username' => 'postgres',
        'password' => 'postgres',
        'port' => 5432,
    ];

    private PostgresDriver $driver;

    protected function setUp(): void
    {
        $this->driver = new PostgresDriver(self::$config);
    }

    public function testImplementsDatabaseInterface(): void
    {
        $this->assertInstanceOf(DatabaseInterface::class, $this->driver);
    }

    public function testConnectsToPostgres(): void
    {
        $this->assertInstanceOf(PDO::class, $this->driver->getPdo());
    }

    public function testQueryReturnsStatement(): void
    {
        $stmt = $this->driver->query('SELECT 1 as test');

        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertSame(1, $stmt->fetch()['test']);
    }

    public function testExecuteReturnsAffectedRows(): void
    {
        $this->driver->execute('CREATE TEMPORARY TABLE test_pg (id SERIAL PRIMARY KEY, name TEXT)');

        $affected = $this->driver->execute("INSERT INTO test_pg (name) VALUES ($1)", ['hello']);

        $this->assertSame(1, $affected);
    }

    public function testLastInsertId(): void
    {
        $this->driver->execute('CREATE TEMPORARY TABLE test_pg2 (id SERIAL PRIMARY KEY, name TEXT)');
        $this->driver->execute("INSERT INTO test_pg2 (name) VALUES ($1)", ['hello']);

        $id = $this->driver->lastInsertId('test_pg2_id_seq');

        $this->assertSame('1', $id);
    }

    public function testConnectionUsesExceptionErrorMode(): void
    {
        $errorMode = $this->driver->getPdo()->getAttribute(PDO::ATTR_ERRMODE);

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $errorMode);
    }

    public function testConnectionUsesFetchAssoc(): void
    {
        $fetchMode = $this->driver->getPdo()->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->assertSame(PDO::FETCH_ASSOC, $fetchMode);
    }

    public function testInvalidHostThrowsConnectionException(): void
    {
        $this->expectException(ConnectionException::class);

        new PostgresDriver([
            'host' => 'invalid-host-that-does-not-exist',
            'database' => 'test',
            'username' => 'test',
            'password' => 'test',
        ]);
    }

    public function testConnectionExceptionHasDebugMessage(): void
    {
        try {
            new PostgresDriver([
                'host' => '127.0.0.1',
                'database' => 'nonexistent_db_that_does_not_exist',
                'username' => 'postgres',
                'password' => 'postgres',
            ]);
        } catch (ConnectionException $e) {
            $this->assertSame('Database connection failed', $e->getMessage());
            $this->assertNotNull($e->getDebugMessage());
            $this->assertStringContainsString('PostgreSQL', $e->getDebugMessage());
            return;
        }

        $this->fail('Expected ConnectionException was not thrown');
    }
}
