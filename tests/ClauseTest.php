<?php

namespace Ludal\QueryBuilder\Tests;

use Ludal\QueryBuilder\Clauses\Clause;
use Ludal\QueryBuilder\Clauses\Select;
use PHPUnit\Framework\TestCase;
use Error;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use PDO;
use stdClass;

final class ClauseTest extends TestCase
{
    /**
     * @var PDO
     */
    private static $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->exec('CREATE TABLE users (id int, name text, city text)');
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function setUp(): void
    {
        self::$pdo->exec('DELETE FROM users');

        for ($i = 0; $i < 10; $i++)
            self::$pdo->exec("INSERT INTO users VALUES ($i, 'User $i', 'City $i')");
    }

    public function testClauseCannotBeInstantiated()
    {
        $this->expectException(Error::class);
        new Clause();
    }

    public function testFetchMethod()
    {
        $select = new Select(self::$pdo);
        $res = $select
            ->select()
            ->from('users')
            ->where('id = 5')
            ->fetch(PDO::FETCH_OBJ);

        $this->assertEquals(5, $res->id);
    }

    public function testFetchAllMethod()
    {
        $select = new Select(self::$pdo);
        $res = $select
            ->select()
            ->from('users')
            ->where('id < 5')
            ->fetchAll(PDO::FETCH_CLASS, stdClass::class);

        $length = count($res);
        $i = 0;

        $this->assertEquals(5, $length);

        foreach ($res as $element) {
            $this->assertInstanceOf(stdClass::class, $element);
            $this->assertEquals("User $i", $element->name);
            $i++;
        }
    }

    public function testInvalidQueryDoesntFetch()
    {
        $this->expectException(InvalidQueryException::class);

        $select = new Select(self::$pdo);
        $select
            ->from('')
            ->fetch();
    }

    public function testInvalidQueryDoesntExecute()
    {
        $this->expectException(InvalidQueryException::class);

        $select = new Select(self::$pdo);
        $select
            ->from('')
            ->execute();
    }
}
