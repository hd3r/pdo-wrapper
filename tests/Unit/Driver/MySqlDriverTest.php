<?php

declare(strict_types=1);

namespace Hd3r\PdoWrapper\Tests\Unit\Driver;

use PDO;
use PHPUnit\Framework\TestCase;
use Hd3r\PdoWrapper\Driver\MySqlDriver;
use Hd3r\PdoWrapper\Exception\ConnectionException;

class MySqlDriverTest extends TestCase
{
    protected function tearDown(): void
    {
        // Clean up ENV after each test
        unset($_ENV['DB_HOST'], $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $_ENV['DB_PORT']);
    }

    public function testThrowsExceptionWhenHostMissing(): void
    {
        $this->expectException(ConnectionException::class);

        new MySqlDriver([
            'database' => 'test',
            'username' => 'root',
        ]);
    }

    public function testThrowsExceptionWhenDatabaseMissing(): void
    {
        $this->expectException(ConnectionException::class);

        new MySqlDriver([
            'host' => 'localhost',
            'username' => 'root',
        ]);
    }

    public function testThrowsExceptionWhenUsernameMissing(): void
    {
        $this->expectException(ConnectionException::class);

        new MySqlDriver([
            'host' => 'localhost',
            'database' => 'test',
        ]);
    }

    public function testExceptionHasDebugMessage(): void
    {
        try {
            new MySqlDriver([]);
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

        // This will throw because host doesn't exist, but it proves ENV is read
        new MySqlDriver();
    }

    public function testArrayConfigOverridesEnv(): void
    {
        $_ENV['DB_HOST'] = 'env-host';
        $_ENV['DB_DATABASE'] = 'env-db';
        $_ENV['DB_USERNAME'] = 'env-user';

        $this->expectException(ConnectionException::class);

        try {
            new MySqlDriver([
                'host' => 'array-host',
                'database' => 'array-db',
                'username' => 'array-user',
            ]);
        } catch (ConnectionException $e) {
            // Verify array config was used, not ENV
            $this->assertStringContainsString('array-host', $e->getDebugMessage());
            throw $e;
        }
    }

    public function testDefaultPortIs3306(): void
    {
        $this->expectException(ConnectionException::class);

        try {
            new MySqlDriver([
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'root',
            ]);
        } catch (ConnectionException $e) {
            $this->assertStringContainsString(':3306', $e->getDebugMessage());
            throw $e;
        }
    }

    public function testCustomPortFromConfig(): void
    {
        $this->expectException(ConnectionException::class);

        try {
            new MySqlDriver([
                'host' => 'localhost',
                'database' => 'test',
                'username' => 'root',
                'port' => 3307,
            ]);
        } catch (ConnectionException $e) {
            $this->assertStringContainsString(':3307', $e->getDebugMessage());
            throw $e;
        }
    }

    public function testCustomPortFromEnv(): void
    {
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_DATABASE'] = 'test';
        $_ENV['DB_USERNAME'] = 'root';
        $_ENV['DB_PORT'] = '3308';

        $this->expectException(ConnectionException::class);

        try {
            new MySqlDriver();
        } catch (ConnectionException $e) {
            $this->assertStringContainsString(':3308', $e->getDebugMessage());
            throw $e;
        }
    }
}
