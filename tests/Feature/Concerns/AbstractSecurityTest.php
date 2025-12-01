<?php

declare(strict_types=1);

namespace Hd3r\PdoWrapper\Tests\Feature\Concerns;

use PHPUnit\Framework\TestCase;
use Hd3r\PdoWrapper\DatabaseInterface;

/**
 * Abstract base class for security tests.
 * Runs identical tests against all database drivers.
 */
abstract class AbstractSecurityTest extends TestCase
{
    protected DatabaseInterface $db;

    abstract protected function createDatabase(): DatabaseInterface;
    abstract protected function getCreateUsersTableSql(): string;
    abstract protected function getCreateSecretsTableSql(): string;

    protected function setUp(): void
    {
        $this->db = $this->createDatabase();
        $this->createSchema();
        $this->seedData();
    }

    protected function createSchema(): void
    {
        $this->db->execute($this->getCreateUsersTableSql());
        $this->db->execute($this->getCreateSecretsTableSql());
    }

    protected function seedData(): void
    {
        $this->db->insert('users', ['name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'admin']);
        $this->db->insert('users', ['name' => 'User', 'email' => 'user@example.com', 'role' => 'user']);
        $this->db->insert('secrets', ['secret_data' => 'TOP SECRET DATA']);
    }

    protected function tearDown(): void
    {
        $this->db->execute('DROP TABLE IF EXISTS secrets');
        $this->db->execute('DROP TABLE IF EXISTS users');
    }

    // =========================================================================
    // SQL INJECTION VIA PARAMETERS (Prepared Statements)
    // =========================================================================

    public function testSqlInjectionInWhereValue(): void
    {
        // Classic SQL injection attempt
        $maliciousInput = "'; DROP TABLE users; --";

        $result = $this->db->table('users')
            ->where('name', $maliciousInput)
            ->get();

        // Should return empty result, NOT drop the table
        $this->assertEmpty($result);

        // Table should still exist with all data
        $users = $this->db->table('users')->get();
        $this->assertCount(2, $users);
    }

    public function testSqlInjectionInWhereValueWithOr(): void
    {
        // Attempt to bypass authentication
        $maliciousInput = "' OR '1'='1";

        $result = $this->db->table('users')
            ->where('name', $maliciousInput)
            ->get();

        // Should NOT return all users
        $this->assertEmpty($result);
    }

    public function testSqlInjectionInInsertValues(): void
    {
        $maliciousName = "Evil'); DROP TABLE secrets; --";
        $maliciousEmail = "evil@example.com', 'admin'); --";

        $this->db->insert('users', [
            'name' => $maliciousName,
            'email' => $maliciousEmail,
        ]);

        // Secrets table should still exist
        $secrets = $this->db->table('secrets')->get();
        $this->assertCount(1, $secrets);

        // User should be inserted with the malicious string as literal value
        $user = $this->db->table('users')->where('name', $maliciousName)->first();
        $this->assertNotNull($user);
        $this->assertSame($maliciousName, $user['name']);
    }

    public function testSqlInjectionInUpdateValues(): void
    {
        $maliciousRole = "admin'; UPDATE users SET role='admin' WHERE '1'='1";

        $this->db->table('users')
            ->where('name', 'User')
            ->update(['role' => $maliciousRole]);

        // Only the target user should be affected
        $admin = $this->db->table('users')->where('name', 'Admin')->first();
        $this->assertSame('admin', $admin['role']); // Still admin

        $user = $this->db->table('users')->where('name', 'User')->first();
        $this->assertSame($maliciousRole, $user['role']); // Literal string, not executed
    }

    public function testSqlInjectionInWhereIn(): void
    {
        $maliciousValues = ["999", "2) OR 1=1; --"];

        try {
            $result = $this->db->table('users')
                ->whereIn('id', $maliciousValues)
                ->get();

            // MySQL casts strings to int (returns 0 or the numeric prefix)
            // Should NOT return all users
            $this->assertLessThan(2, count($result));
        } catch (\Hd3r\PdoWrapper\Exception\QueryException $e) {
            // PostgreSQL throws error on invalid integer - this is GOOD security behavior
            // Check the original PDO exception message
            $originalMessage = $e->getPrevious()?->getMessage() ?? '';
            $this->assertStringContainsString('invalid input syntax', $originalMessage);
        }
    }

    public function testSqlInjectionInWhereBetween(): void
    {
        $maliciousStart = "1; DROP TABLE secrets; --";
        $maliciousEnd = "100";

        try {
            $result = $this->db->table('users')
                ->whereBetween('id', [$maliciousStart, $maliciousEnd])
                ->get();
        } catch (\Hd3r\PdoWrapper\Exception\QueryException $e) {
            // PostgreSQL throws error on invalid integer - this is GOOD security behavior
            $originalMessage = $e->getPrevious()?->getMessage() ?? '';
            $this->assertStringContainsString('invalid input syntax', $originalMessage);
        }

        // Secrets table should still exist regardless of how the DB handled it
        $secrets = $this->db->table('secrets')->get();
        $this->assertCount(1, $secrets);
    }

    public function testSqlInjectionInWhereLike(): void
    {
        $maliciousPattern = "%'; DROP TABLE users; --";

        $result = $this->db->table('users')
            ->whereLike('name', $maliciousPattern)
            ->get();

        // Should return empty (no names match this pattern)
        $this->assertEmpty($result);

        // Table should still exist
        $users = $this->db->table('users')->get();
        $this->assertCount(2, $users);
    }

    public function testSqlInjectionInDirectQuery(): void
    {
        $maliciousId = "1 OR 1=1";

        try {
            // Using parameterized query
            $result = $this->db->query(
                'SELECT * FROM users WHERE id = ?',
                [$maliciousId]
            )->fetchAll(\PDO::FETCH_ASSOC);

            // MySQL casts "1 OR 1=1" to int 1, may return 1 row (id=1)
            // Should NOT return all users
            $this->assertLessThan(2, count($result));
        } catch (\Hd3r\PdoWrapper\Exception\QueryException $e) {
            // PostgreSQL throws error on invalid integer - this is GOOD security behavior
            $originalMessage = $e->getPrevious()?->getMessage() ?? '';
            $this->assertStringContainsString('invalid input syntax', $originalMessage);
        }
    }

    public function testSqlInjectionInDirectQueryWithNamedParams(): void
    {
        $maliciousName = "Admin' OR '1'='1";

        $result = $this->db->query(
            'SELECT * FROM users WHERE name = :name',
            ['name' => $maliciousName]
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Should NOT return all users
        $this->assertEmpty($result);
    }

    // =========================================================================
    // LIMIT / OFFSET INTEGER CASTING
    // =========================================================================

    public function testLimitIntegerCasting(): void
    {
        $result = $this->db->table('users')
            ->limit(1)
            ->get();

        $this->assertCount(1, $result);
    }

    public function testOffsetIntegerCasting(): void
    {
        $result = $this->db->table('users')
            ->limit(10)
            ->offset(1)
            ->get();

        $this->assertCount(1, $result); // Only 1 user left after offset
    }

    // =========================================================================
    // UNION / SUBQUERY INJECTION ATTEMPTS
    // =========================================================================

    public function testUnionInjectionInWhereValue(): void
    {
        $maliciousInput = "' UNION SELECT secret_data, secret_data, secret_data, secret_data FROM secrets --";

        $result = $this->db->table('users')
            ->where('name', $maliciousInput)
            ->get();

        // Should return empty, NOT data from secrets table
        $this->assertEmpty($result);
    }

    // =========================================================================
    // SPECIAL CHARACTERS HANDLING
    // =========================================================================

    public function testSpecialCharactersInValues(): void
    {
        $specialNames = [
            "O'Brien",
            'Quote "Test"',
            "Back\\slash",
            "New\nLine",
            "Tab\tHere",
            "<script>alert('xss')</script>",
            "emoji 🎉 test",
        ];

        foreach ($specialNames as $name) {
            $this->db->insert('users', ['name' => $name, 'email' => 'test@example.com']);

            $user = $this->db->table('users')->where('name', $name)->first();
            $this->assertNotNull($user, "Failed to find user with name: " . addslashes($name));
            $this->assertSame($name, $user['name']);
        }
    }

    public function testEmptyStringValue(): void
    {
        $this->db->insert('users', ['name' => '', 'email' => 'empty@example.com']);

        $user = $this->db->table('users')->where('email', 'empty@example.com')->first();
        $this->assertSame('', $user['name']);
    }

    public function testNullValueHandling(): void
    {
        $this->db->insert('users', [
            'name' => 'NullTest',
            'email' => 'null@example.com',
            'role' => null,
        ]);

        $user = $this->db->table('users')->where('name', 'NullTest')->first();
        $this->assertNull($user['role']);
    }
}
