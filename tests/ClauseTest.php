<?php

namespace Ludal\QueryBuilder\Tests;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Clauses\Clause;
use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\Clauses\Insert;
use Ludal\QueryBuilder\Clauses\Update;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
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

    /**
     * @var Select
     */
    private $select;

    /**
     * @var Insert 
     */
    private $insert;

    /**
     * @var Update
     */
    private $update;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO('sqlite::memory:');

        self::$pdo->exec('CREATE TABLE users (id int, name text, city text)');

        for ($i = 0; $i < 10; $i++)
            self::$pdo->exec("INSERT INTO users VALUES ($i, 'User $i', 'City $i')");
    }

    public function setUp(): void
    {
        // clear db
        self::$pdo->exec('DELETE FROM users');

        // fill db
        for ($i = 0; $i < 10; $i++)
            self::$pdo->exec("INSERT INTO users VALUES ($i, 'User $i', 'City $i')");

        $this->select = new Select(self::$pdo);
        $this->insert = new Insert(self::$pdo);
        $this->update = new Update(self::$pdo);
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public static function getSelect()
    {
        return new Select(self::$pdo);
    }

    public static function getInsert()
    {
        return new Insert(self::$pdo);
    }

    public function testClauseCannotBeInstantiated()
    {
        $this->expectException(Error::class);
        new Clause();
    }

    public function testFetchAsClass()
    {
        $res = $this->select
            ->setColumns()
            ->from('users')
            ->where('id = 5')
            ->setFetchMode(PDO::FETCH_CLASS, stdClass::class)
            ->fetch();

        $this->assertInstanceOf(stdClass::class, $res);
        $this->assertEquals(5, $res->id);
    }

    public function testFetchAllAsClass()
    {
        $res = $this->select
            ->setColumns()
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

        $this->select
            ->from('')
            ->fetch();
    }

    public function testFetchAsObject()
    {
        $result = $this->select
            ->setColumns()
            ->from('users')
            ->fetch(PDO::FETCH_OBJ);

        $this->assertIsObject($result);
    }

    public function testFetchAllAsObject()
    {
        $results = $this->select
            ->setColumns()
            ->from('users')
            ->fetchAll(PDO::FETCH_OBJ);

        foreach ($results as $result)
            $this->assertIsObject($result);
    }

    public function testFetchAsArray()
    {
        $result = $this->select
            ->setColumns()
            ->from('users')
            ->fetch();

        $this->assertIsArray($result);
    }

    public function testFetchAllAsArray()
    {
        $results = $this->select
            ->setColumns()
            ->from('users')
            ->fetchAll();

        foreach ($results as $result)
            $this->assertIsArray($result);
    }

    public function testInvalidQueryDoesntExecute()
    {
        $this->expectException(InvalidQueryException::class);

        $this->select
            ->from('')
            ->execute();
    }

    public function testExecuteWithoutPDO()
    {
        $this->expectException(BadMethodCallException::class);

        (new Select())
            ->setColumns()
            ->from('users')
            ->execute();
    }

    public function testFetchWithoutPDO()
    {
        $this->expectException(BadMethodCallException::class);

        (new Select())
            ->setColumns()
            ->from('users')
            ->fetch();
    }

    public function testSetFetchModeWithoutPDO()
    {
        $this->expectException(BadMethodCallException::class);

        (new Select())
            ->setColumns()
            ->from('users')
            ->setFetchMode(PDO::FETCH_ASSOC);
    }

    public function testSetParamWithoutType()
    {
        $result = $this->select
            ->setColumns()
            ->from('users')
            ->where('id = :id')
            ->setParam(':id', 5)
            ->fetch();

        $this->assertEquals(5, $result['id']);
    }

    public function testSetParamWithType()
    {
        $result = $this->select
            ->setColumns()
            ->from('users')
            ->where('name = :name')
            ->setParam(':name', 'User 3', PDO::PARAM_STR)
            ->fetch();

        $this->assertEquals('User 3', $result['name']);
    }

    public function testSetDefaultFetchMode()
    {
        $result = (new Select(self::$pdo))
            ->setColumns()
            ->from('users')
            ->fetch();

        $this->assertIsArray($result); // default PDO fetch mode

        Clause::setDefaultFetchMode(PDO::FETCH_CLASS, stdClass::class);

        $result = (new Select(self::$pdo))
            ->setColumns()
            ->from('users')
            ->fetch();

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testSetDefaultFetchModeOnTwoDifferentInstances()
    {
        Clause::setDefaultFetchMode(PDO::FETCH_CLASS, stdClass::class);

        $result = $this->select
            ->setColumns()
            ->from('users')
            ->fetch();

        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testFetchWithNoExistingRow()
    {
        $result = $this->select
            ->setColumns()
            ->from('users')
            ->where('id = 20')
            ->fetch();

        $this->assertFalse($result);
    }

    public function testSelectRowCount()
    {
        $count = $this->select
            ->setColumns()
            ->from('users')
            ->rowCount();

        $this->assertEquals(0, $count); // because rowCount doesn't work on SELECT
    }

    public function testInsertRowCountOnInsert()
    {
        $count = $this->insert
            ->into('users')
            ->values(['id' => 10, 'name' => 'User 10'])
            ->rowCount();

        $this->assertEquals(1, $count);
    }

    public function testInsertRowCountOnUpdate()
    {
        $count = $this->update
            ->setTable('users')
            ->set('id = id + 1')
            ->where('id > 5')
            ->rowCount();

        $this->assertEquals(4, $count);
    }

    public function testRowCountWithoutPDOInstance()
    {
        $this->expectException(BadMethodCallException::class);

        (new Select())
            ->setColumns()
            ->from('users')
            ->rowCount();
    }

    public function testSetParams()
    {
        $results = $this->select
            ->setColumns()
            ->from('users')
            ->where('id = :id')
            ->orWhere('name = :name')
            ->setParams([':name' => 'User 0', ':id' => 1])
            ->fetchAll();

        $this->assertEquals(2, count($results));

        $this->assertContains($results[0]->name, ['User 0', 'User 1']);
        $this->assertContains($results[1]->name, ['User 0', 'User 1']);
    }

    public function testSetParamsWithSequentialArrayThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->select
            ->setColumns()
            ->from('users')
            ->where('id = :id')
            ->orWhere('name = :name')
            ->setParams([1, 'User 0']);
    }
}
