<?php

declare(strict_types=1);

namespace Hd3r\PdoWrapper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Hd3r\PdoWrapper\Database;
use Hd3r\PdoWrapper\Driver\MySqlDriver;
use Hd3r\PdoWrapper\Driver\PostgresDriver;
use Hd3r\PdoWrapper\Driver\SqliteDriver;

class DatabaseTest extends TestCase
{
    /**
     * @group mysql
     */
    public function testMysqlReturnsDriver(): void
    {
        $driver = Database::mysql([
            'host' => $_ENV['MYSQL_HOST'] ?? '127.0.0.1',
            'port' => (int) ($_ENV['MYSQL_PORT'] ?? 3306),
            'database' => $_ENV['MYSQL_DATABASE'] ?? 'pdo_wrapper_test',
            'username' => $_ENV['MYSQL_USERNAME'] ?? 'root',
            'password' => $_ENV['MYSQL_PASSWORD'] ?? 'root',
        ]);

        $this->assertInstanceOf(MySqlDriver::class, $driver);
    }

    /**
     * @group postgres
     */
    public function testPostgresReturnsDriver(): void
    {
        $driver = Database::postgres([
            'host' => $_ENV['POSTGRES_HOST'] ?? '127.0.0.1',
            'port' => (int) ($_ENV['POSTGRES_PORT'] ?? 5432),
            'database' => $_ENV['POSTGRES_DATABASE'] ?? 'pdo_wrapper_test',
            'username' => $_ENV['POSTGRES_USERNAME'] ?? 'postgres',
            'password' => $_ENV['POSTGRES_PASSWORD'] ?? 'postgres',
        ]);

        $this->assertInstanceOf(PostgresDriver::class, $driver);
    }

    public function testSqliteReturnsDriver(): void
    {
        $driver = Database::sqlite();

        $this->assertInstanceOf(SqliteDriver::class, $driver);
    }

    public function testSqliteWithPathReturnsDriver(): void
    {
        $driver = Database::sqlite(':memory:');

        $this->assertInstanceOf(SqliteDriver::class, $driver);
    }
}
