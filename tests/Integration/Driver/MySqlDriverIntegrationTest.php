<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Integration\Driver;

use PDO;
use PHPUnit\Framework\TestCase;
use PdoWrapper\Driver\MySqlDriver;

class MySqlDriverIntegrationTest extends TestCase
{
    private static array $config = [
        'host' => '127.0.0.1',
        'database' => 'pdo_wrapper_test',
        'username' => 'root',
        'password' => 'root',
        'port' => 3306,
    ];

    public function testConnectsToMySql(): void
    {
        $driver = new MySqlDriver(self::$config);

        $this->assertInstanceOf(PDO::class, $driver->getPdo());
    }

    public function testCanExecuteQuery(): void
    {
        $driver = new MySqlDriver(self::$config);
        $pdo = $driver->getPdo();

        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();

        $this->assertSame(1, $result['test']);
    }

    public function testConnectionUsesUtf8mb4(): void
    {
        $driver = new MySqlDriver(self::$config);
        $pdo = $driver->getPdo();

        $stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_client'");
        $result = $stmt->fetch();

        $this->assertSame('utf8mb4', $result['Value']);
    }

    public function testConnectionUsesExceptionErrorMode(): void
    {
        $driver = new MySqlDriver(self::$config);
        $pdo = $driver->getPdo();

        $errorMode = $pdo->getAttribute(PDO::ATTR_ERRMODE);

        $this->assertSame(PDO::ERRMODE_EXCEPTION, $errorMode);
    }

    public function testConnectionUsesFetchAssoc(): void
    {
        $driver = new MySqlDriver(self::$config);
        $pdo = $driver->getPdo();

        $fetchMode = $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);

        $this->assertSame(PDO::FETCH_ASSOC, $fetchMode);
    }

    public function testConnectionDisablesEmulatedPrepares(): void
    {
        $driver = new MySqlDriver(self::$config);
        $pdo = $driver->getPdo();

        $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);

        $this->assertFalse($emulate);
    }
}
