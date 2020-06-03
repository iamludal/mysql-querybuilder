<?php

namespace Ludal\QueryBuilder\Tests\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Delete;
use PHPUnit\Framework\TestCase;
use PDO;
use stdClass;

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

    public function invalidTableNames()
    {
        return [
            [2],
            [true],
            [['name']],
            [new stdClass()]
        ];
    }

    /**
     * @dataProvider invalidTableNames
     */
    public function testInvalidTableNames($invalidName)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder->from($invalidName);
    }

    public function testSimpleQuery()
    {
        $sql = $this->builder
            ->from('users')
            ->toSQL();

        $this->assertEquals('DELETE FROM users', $sql);
    }

    public function testQueryWithSimpleCondition()
    {
        $sql = $this->builder
            ->from('users')
            ->where('id = 6')
            ->toSQL();

        $this->assertEquals('DELETE FROM users WHERE (id = 6)', $sql);
    }

    public function testQueryWithMultipleCondition()
    {
        $sql = $this->builder
            ->from('users')
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
            ->from('users')
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
            ->from('users')
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
            ->from('users')
            ->where('id < 5')
            ->rowCount();

        $this->assertEquals(5, $count);
    }
}
