<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Integration\Driver;

use PDO;
use PHPUnit\Framework\TestCase;
use PdoWrapper\Driver\PostgresDriver;

class PostgresDriverIntegrationTest extends TestCase
{
    private static array $config = [
        'host' => '127.0.0.1',
        'database' => 'pdo_wrapper_test',
        'username' => 'postgres',
        'password' => 'postgres',
        'port' => 5432,
    ];

    public function testConnectsToPostgres(): void
    {
        $driver = new PostgresDriver(self::$config);

        $this->assertInstanceOf(PDO::class, $driver->getPdo());
    }

    public function testCanExecuteQuery(): void
    {
        $driver = new PostgresDriver(self::$config);
        $pdo = $driver->getPdo();

        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();

        $this->assertSame(1, $result['test']);
    }

    public function testConnectionUsesExceptionErrorMode(): void
    {
        $driver = new PostgresDriver(self::$config);
        $pdo = $driver->getPdo();

        $errorMode = $pdo->getAttribute(PDO::ATTR_ERRMODE);

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $errorMode);
    }

    public function testConnectionUsesFetchAssoc(): void
    {
        $driver = new PostgresDriver(self::$config);
        $pdo = $driver->getPdo();

        $fetchMode = $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->assertSame(PDO::FETCH_ASSOC, $fetchMode);
    }
}
