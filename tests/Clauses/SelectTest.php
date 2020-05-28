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

    public function testInvalidQuery()
    {
        $invalidQueries = [
            $this->getBuilder(),
            $this->getBuilder()->select(),
            $this->getBuilder()->where('id = 5')
        ];

        $n = count($invalidQueries);
        $count = 0;

        foreach ($invalidQueries as $query) {
            try {
                $query->validate();
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

    public function testSimpleQueryWithWhereClause()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from('users')
            ->where('id = 5')
            ->toSQL();

        $this->assertEquals('SELECT * FROM users WHERE id = 5', $sql);
    }
}