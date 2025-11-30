<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Unit\Driver;

use PHPUnit\Framework\TestCase;
use PdoWrapper\Driver\PostgresDriver;
use PdoWrapper\Exception\ConnectionException;

class PostgresDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['DB_HOST'], $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_PORT']);
    }

    public function testThrowsExceptionWhenHostMissing(): void
    {
        $this->expectException(ConnectionException::class);

        new PostgresDriver([
            'database' => 'test',
            'username' => 'postgres',
        ]);
    }

    public function testThrowsExceptionWhenDatabaseMissing(): void
    {
        $this->expectException(ConnectionException::class);

        new PostgresDriver([
            'host' => 'localhost',
            'username' => 'postgres',
        ]);
    }

    public function testThrowsExceptionWhenUsernameMissing(): void
    {
        $this->expectException(ConnectionException::class);

        new PostgresDriver([
            'host' => 'localhost',
            'database' => 'test',
        ]);
    }

    public function testExceptionHasDebugMessage(): void
    {
        try {
            new PostgresDriver([]);
        } catch (ConnectionException $e) {
            $this->assertSame('Database connection failed', $e->getMessage());
            $this->assertSame('Missing required config: host, database, or username', $e->getDebugMessage());
            return;
        }

        $this->fail('Expected ConnectionException was not thrown');
    }

    public function testReadsConfigFromEnv(): void
    {
        $_ENV['DB_HOST'] = 'invalid-host-that-does-not-exist';
        $_ENV['DB_DATABASE'] = 'testdb';
        $_ENV['DB_USERNAME'] = 'testuser';
        $_ENV['DB_PASSWORD'] = 'testpass';

        $this->expectException(ConnectionException::class);

        new PostgresDriver();
    }

    public function testArrayConfigOverridesEnv(): void
    {
        $_ENV['DB_HOST'] = 'env-host';
        $_ENV['DB_DATABASE'] = 'env-db';
        $_ENV['DB_USERNAME'] = 'env-user';

        $this->expectException(ConnectionException::class);

        try {
            new PostgresDriver([
                'host' => 'array-host',
                'database' => 'array-db',
                'username' => 'array-user',
            ]);
        } catch (ConnectionException $e) {
            $this->assertStringContainsString('array-host', $e->getDebugMessage());
            throw $e;
        }
    }

    public function testDefaultPortIs5432(): void
    {
        $this->expectException(ConnectionException::class);

        try {
            new PostgresDriver([
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'postgres',
            ]);
        } catch (ConnectionException $e) {
            $this->assertStringContainsString(':5432', $e->getDebugMessage());
            throw $e;
        }
    }

    public function testCustomPortFromConfig(): void
    {
        $this->expectException(ConnectionException::class);

        try {
            new PostgresDriver([
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'postgres',
                'port' => 5433,
            ]);
        } catch (ConnectionException $e) {
            $this->assertStringContainsString(':5433', $e->getDebugMessage());
            throw $e;
        }
    }

    public function testCustomPortFromEnv(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_DATABASE'] = 'test';
        $_ENV['DB_USERNAME'] = 'postgres';
        $_ENV['DB_PORT'] = '5434';

        $this->expectException(ConnectionException::class);

        try {
            new PostgresDriver();
        } catch (ConnectionException $e) {
            $this->assertStringContainsString(':5434', $e->getDebugMessage());
            throw $e;
        }
    }
}
