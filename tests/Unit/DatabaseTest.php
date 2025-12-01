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
    public function testMysqlReturnsDriver(): void
    {
        $driver = Database::mysql([
            'host' => '127.0.0.1',
            'database' => 'pdo_wrapper_test',
            'username' => 'root',
            'password' => 'root',
        ]);

        $this->assertInstanceOf(MySqlDriver::class, $driver);
    }

    public function testPostgresReturnsDriver(): void
    {
        $driver = Database::postgres([
            'host' => '127.0.0.1',
            'database' => 'pdo_wrapper_test',
            'username' => 'postgres',
            'password' => 'postgres',
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
