<?php

namespace Ludal\QueryBuilder\Tests;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Clauses\Clause;
use Ludal\QueryBuilder\Clauses\Select;
use PHPUnit\Framework\TestCase;
use BadMethodCallException;
use stdClass;
use Error;
use PDO;

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

        for ($i = 0; $i < 10; $i++)
            self::$pdo->exec("INSERT INTO users VALUES ($i, 'User $i', 'City $i')");
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public static function getSelect()
    {
        return new Select(self::$pdo);
    }

    public function testClauseCannotBeInstantiated()
    {
        $this->expectException(Error::class);
        new Clause();
    }

    public function testFetchAsClass()
    {
        $res = self::getSelect()
            ->select()
            ->from('users')
            ->where('id = 5')
            ->setFetchMode(PDO::FETCH_CLASS, stdClass::class)
            ->fetch();

        $this->assertInstanceOf(stdClass::class, $res);
        $this->assertEquals(5, $res->id);
    }

    public function testFetchAllAsClass()
    {
        $res = self::getSelect()
            ->select()
            ->from('users')
            ->where('id < 5')
            ->fetchAll(PDO::FETCH_CLASS, stdClass::class);

        $length = count($res);

        $this->assertEquals(5, $length);

        foreach ($res as $index => $element) {
            $this->assertInstanceOf(stdClass::class, $element);
            $this->assertEquals("User $index", $element->name);
        }
    }

    public function testInvalidQueryDoesntFetch()
    {
        $this->expectException(InvalidQueryException::class);

        self::getSelect()
            ->from('')
            ->fetch();
    }

    public function testFetchAsObject()
    {
        $result = self::getSelect()
            ->select()
            ->from('users')
            ->fetch(PDO::FETCH_OBJ);

        $this->assertIsObject($result);
    }

    public function testFetchAllAsObject()
    {
        $results = self::getSelect()
            ->select()
            ->from('users')
            ->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $result)
            $this->assertIsObject($result);
    }

    public function testFetchAsArray()
    {
        $result = self::getSelect()
            ->select()
            ->from('users')
            ->fetch();

        $this->assertIsArray($result);
    }

    public function testFetchAllAsArray()
    {
        $results = self::getSelect()
            ->select()
            ->from('users')
            ->fetchAll();

        foreach ($results as $result)
            $this->assertIsArray($result);
    }

    public function testInvalidQueryDoesntExecute()
    {
        $this->expectException(InvalidQueryException::class);

        $this->getSelect()
            ->from('')
            ->execute();
    }

    public function testExecuteWithoutPDO()
    {
        $this->expectException(BadMethodCallException::class);

        (new Select())
            ->select()
            ->from('users')
            ->execute();
    }

    public function testFetchWithoutPDO()
    {
        $this->expectException(BadMethodCallException::class);

        (new Select())
            ->select()
            ->from('users')
            ->fetch();
    }

    public function testSetFetchModeWithoutPDO()
    {
        $this->expectException(BadMethodCallException::class);

        (new Select())
            ->select()
            ->from('users')
            ->setFetchMode(PDO::FETCH_ASSOC);
    }

    public function testSetParamWithoutType()
    {
        $result = $this->getSelect()
            ->select()
            ->from('users')
            ->where('id = :id')
            ->setParam(':id', 5)
            ->fetch();

        $this->assertEquals(5, $result['id']);
    }

    public function testSetParamWithType()
    {
        $result = $this->getSelect()
            ->select()
            ->from('users')
            ->where('name = :name')
            ->setParam(':name', 'User 3', PDO::PARAM_STR)
            ->fetch();

        $this->assertEquals('User 3', $result['name']);
    }
}
