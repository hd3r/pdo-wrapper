<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Feature;

use PdoWrapper\Database;
use PdoWrapper\DatabaseInterface;
use PdoWrapper\Tests\Feature\Concerns\AbstractWorkflowTest;

/**
 * Workflow tests for PostgreSQL driver.
 *
 * @group postgres
 */
class PostgresWorkflowTest extends AbstractWorkflowTest
{
    protected function createDatabase(): DatabaseInterface
    {
        return Database::postgres([
            'host' => '127.0.0.1',
            'port' => 5432,
            'database' => 'pdo_wrapper_test',
            'username' => 'postgres',
            'password' => 'postgres',
        ]);
    }

    protected function getCreateUsersTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT \'user\',
            active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )';
    }

    protected function getCreatePostsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS posts (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL REFERENCES users(id),
            title VARCHAR(255) NOT NULL,
            content TEXT,
            status VARCHAR(50) DEFAULT \'draft\',
            views INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )';
    }

    protected function getCreateCommentsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS comments (
            id SERIAL PRIMARY KEY,
            post_id INTEGER NOT NULL REFERENCES posts(id),
            user_id INTEGER NOT NULL REFERENCES users(id),
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )';
    }

    protected function getCreateTagsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS tags (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL
        )';
    }

    protected function getCreatePostTagsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS post_tags (
            post_id INTEGER NOT NULL REFERENCES posts(id),
            tag_id INTEGER NOT NULL REFERENCES tags(id),
            PRIMARY KEY (post_id, tag_id)
        )';
    }
}
