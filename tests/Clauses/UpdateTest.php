<?php

namespace Ludal\QueryBuilder\Tests\Clauses;

use Ludal\QueryBuilder\Clauses\Update;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use PHPUnit\Framework\TestCase;
use PDO;

final class UpdateTest extends TestCase
{
    /**
     * @var Update
     */
    private $builder;

    /**
     * @var PDO
     */
    private static $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->exec('CREATE TABLE users (id int, name text, city text)');
    }

    public function setUp(): void
    {
        self::$pdo->exec('DELETE FROM users');

        for ($i = 0; $i < 10; $i++)
            self::$pdo->exec("INSERT INTO users VALUES ($i, 'User $i', 'City $i')");

        $this->builder = new Update(self::$pdo);
    }

    public function testSimpleQuery()
    {
        $sql = $this->builder
            ->setTable('users')
            ->set(['name' => 'John', 'age' => 20])
            ->toSQL();

        $expected = 'UPDATE users SET name = :_name, age = :_age';

        $this->assertEquals($expected, $sql);
    }

    public function testSimpleQueryWithWhere()
    {
        $sql = $this->builder
            ->setTable('users')
            ->set(['name' => 'John', 'age' => 20])
            ->where('id = 8')
            ->toSQL();

        $expected = 'UPDATE users SET name = :_name, age = :_age WHERE (id = 8)';

        $this->assertEquals($expected, $sql);
    }

    public function testSetWithDifferentTypes()
    {
        $sql = $this->builder
            ->setTable('users')
            ->set(['name' => 'John', 'age' => 20], 'id = 20')
            ->toSQL();

        $expected = 'UPDATE users SET name = :_name, age = :_age, id = 20';

        $this->assertEquals($expected, $sql);
    }

    public function testQueryWithComplexConditions()
    {
        $sql = $this->builder
            ->setTable('cars')
            ->set(['price' => 1500])
            ->where('year < 2000', 'brand = "Peugeot"')
            ->orWhere('km > 100000')
            ->orWhere('color = "red"')
            ->toSQL();

        $expected = 'UPDATE cars SET price = :_price ';
        $expected .= 'WHERE (year < 2000 AND brand = "Peugeot") ';
        $expected .= 'OR (km > 100000) OR (color = "red")';

        $this->assertEquals($expected, $sql);
    }

    public function testQueryUpdatesTheDatabase()
    {
        $user = self::$pdo->query('SELECT * FROM users WHERE id = 9')
            ->fetch();

        $this->assertEquals('User 9', $user['name']);


        $this->builder
            ->setTable('users')
            ->set(['name' => 'New user 9'])
            ->where('id = 9')
            ->execute();

        $user = self::$pdo->query('SELECT * FROM users WHERE id = 9')
            ->fetch();

        $this->assertEquals('New user 9', $user['name']);
    }

    public function testInvalidQuery()
    {
        $this->expectException(InvalidQueryException::class);

        $this->builder
            ->where('id = 5')
            ->toSQL();
    }

    public function testInvalidQueryBis()
    {
        $this->expectException(InvalidQueryException::class);

        $this->builder
            ->set(['name' => 'John'])
            ->where('id = 5')
            ->toSQL();
    }
}
