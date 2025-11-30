<?php

declare(strict_types=1);

namespace PdoWrapper;

use PdoWrapper\Driver\MySqlDriver;
use PdoWrapper\Driver\PostgresDriver;
use PdoWrapper\Driver\SqliteDriver;

class Database
{
    /**
     * @param array{host?: string, database?: string, username?: string, password?: string, port?: int, charset?: string, options?: array} $config
     */
    public static function mysql(array $config = []): MySqlDriver
    {
        return new MySqlDriver($config);
    }

    /**
     * @param array{host?: string, database?: string, username?: string, password?: string, port?: int, options?: array} $config
     */
    public static function postgres(array $config = []): PostgresDriver
    {
        return new PostgresDriver($config);
    }

    public static function sqlite(?string $path = null): SqliteDriver
    {
        return new SqliteDriver($path);
    }
}
