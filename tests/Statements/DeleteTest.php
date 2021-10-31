<?php

namespace Ludal\QueryBuilder\Tests\Statements;

use Ludal\QueryBuilder\Enums\Order;
use Ludal\QueryBuilder\Statements\Delete;
use PDO;
use PHPUnit\Framework\TestCase;

final class DeleteTest extends TestCase
{
    /**
     * @var Delete
     */
    private $builder;

    /**
     * @var PDO
     */
    private static $pdo;

    public function setUp(): void
    {
        self::$pdo->exec('DELETE FROM users');

        for ($i = 0; $i < 10; $i++)
            self::$pdo->exec("INSERT INTO users VALUES ($i, 'User $i')");

        $this->builder = new Delete(self::$pdo);
    }

    public static function setupBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->exec('CREATE TABLE users (id int, username text)');
    }

    public function testSimpleQuery()
    {
        $sql = $this->builder
            ->setTable('users')
            ->toSQL();

        $this->assertEquals('DELETE FROM users', $sql);
    }

    public function testQueryWithSimpleCondition()
    {
        $sql = $this->builder
            ->setTable('users')
            ->where('id = 6')
            ->toSQL();

        $this->assertEquals('DELETE FROM users WHERE (id = 6)', $sql);
    }

    public function testQueryWithMultipleCondition()
    {
        $sql = $this->builder
            ->setTable('users')
            ->where('id = 6', 'age < 18')
            ->orWhere('name = :name')
            ->toSQL();

        $this->assertEquals('DELETE FROM users WHERE (id = 6 AND age < 18) OR (name = :name)', $sql);
    }

    public function testRowsAreDeleted()
    {
        $results = self::$pdo->query('SELECT * FROM users WHERE id < 5')
            ->fetchAll();

        $this->assertCount(5, $results);

        $this->builder
            ->setTable('users')
            ->where('id < 5')
            ->execute();

        $results = self::$pdo->query('SELECT * FROM users WHERE id < 5')
            ->fetchAll();

        $this->assertCount(0, $results);
    }

    public function testRowsAreDeletedWithParam()
    {
        $results = self::$pdo->query('SELECT * FROM users WHERE id < 5')
            ->fetchAll();

        $this->assertCount(5, $results);

        $this->builder
            ->setTable('users')
            ->where('id < :id')
            ->setParam(':id', 5)
            ->execute();

        $results = self::$pdo->query('SELECT * FROM users WHERE id < 5')
            ->fetchAll();

        $this->assertCount(0, $results);
    }

    public function testRowCount()
    {
        $count = $this->builder
            ->setTable('users')
            ->where('id < 5')
            ->rowCount();

        $this->assertEquals(5, $count);
    }

    public function testDeleteWithOrderBy() {
        $actualSql = $this->builder
            ->setTable('users')
            ->where('id < :id')
            ->orderBy(['id'])
            ->toSQL();

        $expectedSql = "DELETE FROM users WHERE (id < :id) ORDER BY id";

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testDeleteWithLimit() {
        $actualSql = $this->builder
            ->setTable('users')
            ->limit(5)
            ->toSQL();

        $expectedSql = "DELETE FROM users LIMIT 5";

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testDeleteWithOrderByAndLimit() {
        $actualSql = $this->builder
            ->setTable('users')
            ->where('id = :id')
            ->orderBy(['id' => Order::DESC, 'name'])
            ->limit(10)
            ->toSQL();

        $expectedSql = "DELETE FROM users WHERE (id = :id) ORDER BY id DESC, name LIMIT 10";

        $this->assertEquals($expectedSql, $actualSql);
    }
}
