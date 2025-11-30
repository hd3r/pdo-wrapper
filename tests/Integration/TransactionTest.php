<?php

declare(strict_types=1);

namespace PdoWrapper\Tests\Integration;

use Exception;
use PHPUnit\Framework\TestCase;
use PdoWrapper\Database;
use PdoWrapper\DatabaseInterface;
use PdoWrapper\Exception\TransactionException;

class TransactionTest extends TestCase
{
    private DatabaseInterface $db;

    protected function setUp(): void
    {
        $this->db = Database::sqlite();
        $this->db->execute('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    }

    public function testManualTransactionCommit(): void
    {
        $this->db->beginTransaction();
        $this->db->execute("INSERT INTO users (name) VALUES (?)", ['Max']);
        $this->db->commit();

        $stmt = $this->db->query('SELECT * FROM users');
        $users = $stmt->fetchAll();

        $this->assertCount(1, $users);
        $this->assertSame('Max', $users[0]['name']);
    }

    public function testManualTransactionRollback(): void
    {
        $this->db->beginTransaction();
        $this->db->execute("INSERT INTO users (name) VALUES (?)", ['Max']);
        $this->db->rollback();

        $stmt = $this->db->query('SELECT * FROM users');
        $users = $stmt->fetchAll();

        $this->assertCount(0, $users);
    }

    public function testTransactionCallbackCommitsOnSuccess(): void
    {
        $result = $this->db->transaction(function (DatabaseInterface $db) {
            $db->execute("INSERT INTO users (name) VALUES (?)", ['Max']);
            $db->execute("INSERT INTO users (name) VALUES (?)", ['Anna']);
            return 'success';
        });

        $this->assertSame('success', $result);

        $stmt = $this->db->query('SELECT * FROM users');
        $users = $stmt->fetchAll();

        $this->assertCount(2, $users);
    }

    public function testTransactionCallbackRollsBackOnException(): void
    {
        try {
            $this->db->transaction(function (DatabaseInterface $db) {
                $db->execute("INSERT INTO users (name) VALUES (?)", ['Max']);
                throw new Exception('Something went wrong');
            });
        } catch (Exception $e) {
            $this->assertSame('Something went wrong', $e->getMessage());
        }

        $stmt = $this->db->query('SELECT * FROM users');
        $users = $stmt->fetchAll();

        $this->assertCount(0, $users);
    }

    public function testTransactionHooksAreTriggered(): void
    {
        $events = [];

        $this->db->on('transaction.begin', function () use (&$events) {
            $events[] = 'begin';
        });

        $this->db->on('transaction.commit', function () use (&$events) {
            $events[] = 'commit';
        });

        $this->db->transaction(function (DatabaseInterface $db) {
            $db->execute("INSERT INTO users (name) VALUES (?)", ['Max']);
        });

        $this->assertSame(['begin', 'commit'], $events);
    }

    public function testTransactionRollbackHookIsTriggered(): void
    {
        $rollbackTriggered = false;

        $this->db->on('transaction.rollback', function () use (&$rollbackTriggered) {
            $rollbackTriggered = true;
        });

        try {
            $this->db->transaction(function () {
                throw new Exception('Fail');
            });
        } catch (Exception $e) {
            // Expected
        }

        $this->assertTrue($rollbackTriggered);
    }
}
