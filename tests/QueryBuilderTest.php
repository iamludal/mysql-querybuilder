<?php

namespace Ludal\QueryBuilder\Tests;

use Ludal\QueryBuilder\Clauses\Insert;
use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\Clauses\Update;
use Ludal\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Delete;
use stdClass;
use PDO;

final class QueryBuilderTest extends TestCase
{
    /**
     * @var PDO
     */
    private static $pdo;

    /**
     * @var QueryBuilder
     */
    private $builder;

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

        $this->builder = new QueryBuilder();
        $this->builderWithPDO = new QueryBuilder(self::$pdo);
    }

    public function invalidConstructorArguments()
    {
        return [
            [4],
            [true],
            ['String'],
            [new stdClass()]
        ];
    }

    /**
     * @dataProvider invalidConstructorArguments
     */
    public function testInvalidConstructorArgumentsThrowsException($invalidArgument)
    {
        $this->expectException(InvalidArgumentException::class);
        new QueryBuilder($invalidArgument);
    }

    public function testSelectReturnsInstanceOfSelect()
    {
        $select = $this->builder->select();
        $this->assertInstanceOf(Select::class, $select);
    }

    public function testInsertReturnsInstanceOfInsert()
    {
        $res = $this->builder
            ->insertInto('articles');

        $this->assertInstanceOf(Insert::class, $res);
    }

    public function testUpdateReturnsInstanceOfUpdate()
    {
        $update = $this->builder->update('users');

        $this->assertInstanceOf(Update::class, $update);
    }

    public function testDeleteReturnsInstanceOfDelete()
    {
        $res = $this->builder
            ->deleteFrom('articles');

        $this->assertInstanceOf(Delete::class, $res);
    }

    public function testSetDefaultFetchMode()
    {
        QueryBuilder::setDefaultFetchMode(PDO::FETCH_ASSOC);

        $results = (new QueryBuilder(self::$pdo))
            ->select()
            ->from('users')
            ->fetchAll();

        foreach ($results as $result)
            $this->assertIsArray($result);

        QueryBuilder::setDefaultFetchMode(PDO::FETCH_OBJ);

        $results = (new QueryBuilder(self::$pdo))
            ->select()
            ->from('users')
            ->fetchAll();

        foreach ($results as $result)
            $this->assertIsObject($result);
    }
}
