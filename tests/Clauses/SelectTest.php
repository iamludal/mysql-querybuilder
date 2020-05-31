<?php

namespace Ludal\QueryBuilder\Tests;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Clauses\Select;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
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

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->query('CREATE TABLE users (
            `id` INTEGER CONSTRAINT users_pk primary key autoincrement,
            `name` TEXT,
            `address` TEXT,
            `city` TEXT)');
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
            ->select('name', ['city' => 'c', 'age' => 'a'])
            ->from('users')
            ->toSQL();

        $this->assertEquals('SELECT name, city AS c, age AS a FROM users', $sql);
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
        $sql1 = $this->getBuilder()
            ->select()
            ->from('cars')
            ->limit(5, 10)
            ->toSQL();

        $sql2 = $this->getBuilder()
            ->select()
            ->from('cars')
            ->limit(10)
            ->offset(5)
            ->toSQL();

        $expected = 'SELECT * FROM cars LIMIT 10 OFFSET 5';

        $this->assertEquals($expected, $sql1);
        $this->assertEquals($expected, $sql2);
    }

    public function testOffset()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('cars')
            ->offset(5)
            ->toSQL();

        $this->assertEquals('SELECT * FROM cars OFFSET 5', $sql);
    }

    public function invalidOffsets()
    {
        return [
            ["3"],
            [true],
            [new stdClass()],
            [4.3]
        ];
    }

    /**
     * @dataProvider invalidOffsets
     */
    public function testInvalidOffset($invalidOffset)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getBuilder()
            ->select()
            ->from('cars')
            ->offset($invalidOffset);
    }

    public function testSelectAsSequential()
    {
        $sql = $this->getBuilder()
            ->select(['name', 'age'])
            ->from('users')
            ->toSQL();

        $this->assertEquals('SELECT name, age FROM users', $sql);
    }

    public function testComplexQuery()
    {
        $sql = $this->getBuilder()
            ->select(['name' => 'n'], 'age')
            ->from('users')
            ->where('id < :id', 'age < 20')
            ->orWhere('country = "FR"')
            ->orWhere('id < 30')
            ->orderBy('age', 'desc')
            ->orderBy('name')
            ->limit(10)
            ->offset(5)
            ->toSQL();

        $expected = 'SELECT name AS n, age FROM users ';
        $expected .= 'WHERE (id < :id AND age < 20) OR (country = "FR") OR (id < 30) ';
        $expected .= 'ORDER BY age DESC, name ASC LIMIT 10 OFFSET 5';

        $this->assertEquals($expected, $sql);
    }
}
