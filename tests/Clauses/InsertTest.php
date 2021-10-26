<?php

namespace Ludal\QueryBuilder\Tests\Clauses;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Clauses\Insert;
use PHPUnit\Framework\TestCase;
use PDO;

final class InsertTest extends TestCase
{
    /**
     * @var PDO
     */
    private static $pdo;

    public function getBuilder(): Insert
    {
        return new Insert();
    }

    public function getBuilderWithPDO(): Insert
    {
        return new Insert(self::$pdo);
    }

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->exec('CREATE TABLE users (id int, username text)');
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testInvalidQueries()
    {
        $invalidQueries = [
            $this->getBuilder(),
            $this->getBuilder()->values(['name' => 'John'])
        ];

        foreach ($invalidQueries as $invalidQuery)
            try {
                $invalidQuery->toSQL();
                $this->fail('Invalid query is supposed to raise exception');
            } catch (InvalidQueryException $e) {
            }
    }


    public function testSimpleInsert()
    {
        $sql = $this->getBuilder()
            ->setTable('users')
            ->values(['username' => 'Billy', 'id' => 5])
            ->toSQL();

        $expected = 'INSERT INTO users (username, id) VALUES (:_username, :_id)';

        $this->assertEquals($expected, $sql);
    }

    public function testIncompleteQuery()
    {
        $this->expectException(InvalidQueryException::class);

        $this->getBuilder()
            ->values(['username' => 'Billy', 'id' => 5])
            ->toSQL();
    }

    public function testRowIsInserted()
    {
        $this->getBuilderWithPDO()
            ->setTable('users')
            ->values(['username' => 'Bob', 'id' => 5])
            ->execute();

        $res = self::$pdo->query('SELECT * FROM users')->fetchAll();

        $this->assertCount(1, $res);
        $this->assertEquals('Bob', $res[0]['username']);
    }
}
