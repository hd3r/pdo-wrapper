<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Feature;

use PdoWrapper\Database;
use PdoWrapper\DatabaseInterface;
use PdoWrapper\Tests\Feature\Concerns\AbstractWorkflowTest;

/**
 * Workflow tests for MySQL driver.
 *
 * @group mysql
 */
class MySqlWorkflowTest extends AbstractWorkflowTest
{
    protected function createDatabase(): DatabaseInterface
    {
        return Database::mysql([
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'pdo_wrapper_test',
            'username' => 'root',
            'password' => 'root',
        ]);
    }

    protected function getCreateUsersTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT "user",
            active TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    protected function getCreatePostsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            status VARCHAR(50) DEFAULT "draft",
            views INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    protected function getCreateCommentsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    protected function getCreateTagsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    protected function getCreatePostTagsTableSql(): string
    {
        return 'CREATE TABLE IF NOT EXISTS post_tags (
            post_id INT NOT NULL,
            tag_id INT NOT NULL,
            PRIMARY KEY (post_id, tag_id),
            FOREIGN KEY (post_id) REFERENCES posts(id),
            FOREIGN KEY (tag_id) REFERENCES tags(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }
}
