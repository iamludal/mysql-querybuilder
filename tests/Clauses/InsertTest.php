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

    public function getBuilder()
    {
        return new Insert();
    }

    public function getBuilderWithPDO()
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
            $this->getBuilder()->values(['name' => 'John']),
            $this->getBuilder()->returning('id')
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
            ->into('users')
            ->values(['username' => 'Billy', 'id' => 5])
            ->toSQL();

        $expected = 'INSERT INTO users (username, id) VALUES (:v1, :v2)';

        $this->assertEquals($expected, $sql);
    }

    public function testInsertWithReturning()
    {
        $sql = $this->getBuilder()
            ->into('cars')
            ->values(['brand' => 'BMW', 'id' => 5])
            ->returning('id', 'username')
            ->toSQL();

        $expected = 'INSERT INTO cars (brand, id) VALUES (:v1, :v2) RETURNING id, username';

        $this->assertEquals($expected, $sql);
    }

    public function testRowIsInserted()
    {
        $this->getBuilderWithPDO()
            ->into('users')
            ->values(['username' => 'Bob', 'id' => 5])
            ->execute();

        $stmt = self::$pdo->prepare('SELECT * FROM users');
        $stmt->execute();
        $res = $stmt->fetchAll();

        $this->assertEquals(1, count($res));
        $this->assertEquals('Bob', $res[0]['username']);
    }
}
