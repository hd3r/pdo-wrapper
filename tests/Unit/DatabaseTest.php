<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PdoWrapper\Database;
use PdoWrapper\Driver\MySqlDriver;
use PdoWrapper\Driver\PostgresDriver;
use PdoWrapper\Driver\SqliteDriver;

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
