<?php

namespace Ludal\QueryBuilder\Tests\Statements;

use Ludal\QueryBuilder\Statements\Select;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use PDO;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

final class SelectTest extends TestCase
{
    /**
     * @var PDO
     */
    private static $pdo;

    /**
     * @var Select
     */
    private $builder;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->query('CREATE TABLE users (
            `id` INTEGER CONSTRAINT users_pk primary key autoincrement,
            `name` TEXT,
            `address` TEXT,
            `city` TEXT)');
    }

    public function setUp(): void
    {
        $this->builder = new Select(self::$pdo);
    }

    public function badConstructorArguments(): array
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
        $this->expectException(TypeError::class);

        new Select($badArgument);
    }

    public function goodConstructorArguments(): array
    {
        return [
            [null],
            [new PDO('sqlite::memory:')]
        ];
    }

    /**
     * @dataProvider goodConstructorArguments
     */
    public function testValidConstructorArguments($goodArgument)
    {
        $select = new Select($goodArgument);
        $this->assertNotNull($select);
    }

    public function testSelectMethodReturnsTheInstance()
    {
        $select = $this->builder->setColumns();

        $this->assertInstanceOf(Select::class, $select);
    }

    public function testInvalidQueries()
    {
        $invalidQueries = [
            (new Select()),
            (new Select())->setColumns(),
            (new Select())->from('users'),
            (new Select())->where('id = 5')
        ];

        $expectedCount = count($invalidQueries);
        $actualCount = 0;

        foreach ($invalidQueries as $query) {
            try {
                $query->toSQL();
            } catch (InvalidQueryException $e) {
                $actualCount++;
            }
        }

        $this->assertEquals($expectedCount, $actualCount);
    }

    public function testSimpleQuery()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('users')
            ->toSQL();

        $this->assertEquals('SELECT * FROM users', $sql);
    }

    public function testSimpleQueryWithColumnNames()
    {
        $sql = $this->builder
            ->setColumns('name', ['city' => 'c', 'age' => 'a'])
            ->from('users')
            ->toSQL();

        $this->assertEquals('SELECT name, city AS c, age AS a FROM users', $sql);
    }

    public function testQueryWithWhereClause()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('users')
            ->where('id = 5')
            ->toSQL();

        $this->assertEquals('SELECT * FROM users WHERE (id = 5)', $sql);
    }

    public function testQueryWithMultipleWhereClauses()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('users')
            ->where('id = 5', 'age < 18')
            ->toSQL();

        $expected = 'SELECT * FROM users WHERE (id = 5 AND age < 18)';

        $this->assertEquals($expected, $sql);
    }

    public function testWhereAsAssociativeArray()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('users')
            ->where(['id' => 5, 'age' => 18])
            ->toSQL();

        $expected = 'SELECT * FROM users WHERE (id = :_id AND age = :_age)';

        $this->assertEquals($expected, $sql);
    }

    public function testWhereOrWhere()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('cars')
            ->where('doors = 5', 'year < 2000')
            ->orWhere('km < 1000')
            ->orWhere('seats = 2', 'wheels = 2')
            ->toSQL();

        $expected = 'SELECT * FROM cars WHERE (doors = 5 AND year < 2000) OR (km < 1000) OR (seats = 2 AND wheels = 2)';

        $this->assertEquals($expected, $sql);
    }

    public function testQueryWithGroupByClause()
    {
        $sql = $this->builder
            ->setColumns('COUNT(*)')
            ->from('users')
            ->groupBy('group_id')
            ->toSQL();

        $this->assertEquals('SELECT COUNT(*) FROM users GROUP BY group_id', $sql);
    }

    public function testQueryWithMultipleGroupByClauses()
    {
        $sql = $this->builder
            ->setColumns('COUNT(*)')
            ->from('users')
            ->groupBy('group_id', 'second_group_id')
            ->toSQL();

        $expected = 'SELECT COUNT(*) FROM users GROUP BY group_id, second_group_id';

        $this->assertEquals($expected, $sql);
    }

    public function testGroupByAsAssociativeArray()
    {
        $sql = $this->builder
            ->setColumns('COUNT(*)')
            ->from('users')
            ->groupBy(['group_id' => 'ASC', 'second_group_id' => 'DESC'])
            ->toSQL();

        $expected = 'SELECT COUNT(*) FROM users GROUP BY group_id ASC, second_group_id DESC';

        $this->assertEquals($expected, $sql);
    }

    public function testOrderBy()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('cars')
            ->orderBy('brand', 'desc')
            ->orderBy('year')
            ->toSQL();

        $expected = 'SELECT * FROM cars ORDER BY brand DESC, year ASC';

        $this->assertEquals($expected, $sql);
    }

    public function testLimit()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('cars')
            ->limit(10)
            ->toSQL();

        $this->assertEquals('SELECT * FROM cars LIMIT 10', $sql);
    }

    public function testLimitAndOffset()
    {
        $sql = (new Select())
            ->setColumns()
            ->from('cars')
            ->limit(10)
            ->offset(5)
            ->toSQL();

        $expected = 'SELECT * FROM cars LIMIT 10 OFFSET 5';

        $this->assertEquals($expected, $sql);
    }

    public function testOffset()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('cars')
            ->offset(5)
            ->toSQL();

        $this->assertEquals('SELECT * FROM cars OFFSET 5', $sql);
    }

    public function testSelectAsSequential()
    {
        $sql = $this->builder
            ->setColumns(['name', 'age'])
            ->from('users')
            ->toSQL();

        $this->assertEquals('SELECT name, age FROM users', $sql);
    }

    public function testComplexQuery()
    {
        $sql = $this->builder
            ->setColumns(['name' => 'n'], 'age')
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

    public function testWhereWithEmptyArray()
    {
        $sql = $this->builder
            ->setColumns()
            ->from('users')
            ->where([])
            ->toSQL();

        $expected = "SELECT * FROM users";

        $this->assertEquals($expected, $sql);
    }
}
