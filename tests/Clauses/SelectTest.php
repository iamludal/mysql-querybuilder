<?php

namespace Ludal\QueryBuilder\Tests;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use PHPUnit\Framework\TestCase;
use stdClass;
use PDO;

final class SelectTest extends TestCase
{
    /**
     * @var PDO
     */
    private static $pdo;

    public static function getBuilder()
    {
        return new Select();
    }

    public static function getBuilderWithPDO()
    {
        return new Select($this->pdo);
    }

    /**
     * @beforeClass
     */
    public static function initPDO()
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->query('CREATE TABLE users (
            `id` INTEGER CONSTRAINT users_pk primary key autoincrement,
            `name` TEXT,
            `address` TEXT,
            `city` TEXT)');
    }

    /**
     * @before
     */
    public function clearAndFillDatabase()
    {
        self::$pdo->exec('DELETE FROM users');

        for ($i = 1; $i <= 10; $i++) {
            $sql = "INSERT INTO users (`name`, `address`, `city`) VALUES ('User $i', 'Address $i', 'City $i')";
            self::$pdo->exec($sql);
        }

        self::$pdo->exec("UPDATE users SET city = NULL WHERE id = 9");
    }

    public function badConstructorArguments()
    {
        return [
            [0],
            [12],
            [''],
            ['Hello'],
            [true],
            [false],
            [new stdClass()],
        ];
    }

    /**
     * @dataProvider badConstructorArguments
     */
    public function testInvalidConstructorArguments($badArgument)
    {
        $this->expectException(InvalidArgumentException::class);

        new Select($badArgument);
    }

    public function goodConstructorArguments()
    {
        return [
            [null],
            [new PDO('sqlite::memory:')]
        ];
    }

    /**
     * @dataProvider goodConstructorArguments
     * @doesNotPerformAssertions
     */
    public function testValidConstructorArguments($goodArgument)
    {
        new Select($goodArgument);
    }

    public function testSelectMethodReturnsTheInstance()
    {
        $select = $this->getBuilder()->select();

        $this->assertInstanceOf(Select::class, $select);
    }

    public function testInvalidQueries()
    {
        $invalidQueries = [
            $this->getBuilder(),
            $this->getBuilder()->select(),
            $this->getBuilder()->from('users'),
            $this->getBuilder()->where('id = 5')
        ];

        $n = count($invalidQueries);
        $count = 0;

        foreach ($invalidQueries as $query) {
            try {
                $query->toSQL();
            } catch (InvalidQueryException $e) {
                $count++;
            }
        }

        $this->assertEquals($n, $count);
    }

    public function testSimpleQuery()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('users')
            ->toSQL();

        $this->assertEquals('SELECT * FROM users', $sql);
    }

    public function testSimpleQueryWithColumnNames()
    {
        $sql = $this->getBuilder()
            ->select('name', ['city', 'c'])
            ->from('users')
            ->toSQL();

        $this->assertEquals('SELECT name, city AS c FROM users', $sql);
    }

    public function invalidTableNames()
    {
        return [
            [1],
            [9],
            [false],
            [new stdClass()]
        ];
    }

    /**
     * @dataProvider invalidTableNames
     */
    public function testInvalidTableNames($invalidName)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getBuilder()
            ->select()
            ->from($invalidName);
    }

    public function testQueryWithWhereClause()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('users')
            ->where('id = 5')
            ->toSQL();

        $this->assertEquals('SELECT * FROM users WHERE (id = 5)', $sql);
    }

    public function testQueryWithMultipleWhereClauses()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('users')
            ->where('id = 5', 'age < 18')
            ->toSQL();

        $expected = 'SELECT * FROM users WHERE (id = 5 AND age < 18)';

        $this->assertEquals($expected, $sql);
    }

    public function testEmptyWhereIsLikeNotHavingIt()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('users')
            ->where()
            ->toSQL();

        $this->assertEquals('SELECT * FROM users', $sql);
    }

    public function testWhereOrWhere()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('cars')
            ->where('doors = 5', 'year < 2000')
            ->orWhere('km < 1000')
            ->orWhere('seats = 2', 'wheels = 2')
            ->toSQL();

        $expected = 'SELECT * FROM cars WHERE (doors = 5 AND year < 2000) OR (km < 1000) OR (seats = 2 AND wheels = 2)';

        $this->assertEquals($expected, $sql);
    }

    public function testOrderBy()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('cars')
            ->orderBy('brand', 'desc')
            ->orderBy('year')
            ->toSQL();

        $expected = 'SELECT * FROM cars ORDER BY brand DESC, year ASC';

        $this->assertEquals($expected, $sql);
    }

    public function testLimit()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('cars')
            ->limit(10)
            ->toSQL();

        $this->assertEquals('SELECT * FROM cars LIMIT 10', $sql);
    }

    public function invalidLimits()
    {
        return [
            ['12'],
            [true],
            [new stdClass()],
            [3.5]
        ];
    }

    /**
     * @dataProvider invalidLimits
     */
    public function testInvalidLimits($invalidLimit)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getBuilder()
            ->select()
            ->from('cars')
            ->limit($invalidLimit);
    }

    public function testLimitAndOffset()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('cars')
            ->limit(5, 10)
            ->toSQL();

        $this->assertEquals('SELECT * FROM cars LIMIT 10 OFFSET 5', $sql);
    }
}
