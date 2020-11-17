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
use PDOStatement;

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

        self::$pdo->exec('CREATE TABLE users (id int unique, name text, city text)');

        for ($i = 0; $i < 10; $i++)
            self::$pdo->exec("INSERT INTO users VALUES ($i, 'User $i', 'City $i')");
    }

    public function setUp(): void
    {
        // clear dbexecute
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

        $this->assertCount(5, $res);

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
        $id = $this->select
            ->setColumns('id')
            ->from('users')
            ->where('id = :id')
            ->setParam(':id', 5)
            ->fetch(PDO::FETCH_COLUMN);

        $this->assertEquals(5, $id);
    }

    public function testSetParamWithType()
    {
        $name = $this->select
            ->setColumns('name')
            ->from('users')
            ->where('name = :name')
            ->setParam(':name', 'User 3', PDO::PARAM_STR)
            ->fetch(PDO::FETCH_COLUMN);

        $this->assertEquals('User 3', $name);
    }

    public function testSetParamsUsingCompact()
    {
        $name = 'User 3';
        $city = 'City 4';

        $results = $this->select
            ->setColumns('name', 'city')
            ->from('users')
            ->where('name = :name')
            ->orWhere('city = :city')
            ->orderBy('id')
            ->setParams(compact('name', 'city'))
            ->fetchAll(PDO::FETCH_OBJ);

        $this->assertEquals(2, count($results));
        $this->assertEquals($name, $results[0]->name);
        $this->assertEquals($city, $results[1]->city);
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
            ->set('id = id + 10')
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
            ->setColumns('name')
            ->from('users')
            ->where('id = :id')
            ->orWhere('name = :name')
            ->setParams([':name' => 'User 0', ':id' => 1])
            ->fetchAll(PDO::FETCH_COLUMN);

        $this->assertCount(2, $results);

        $this->assertContains($results[0], ['User 0', 'User 1']);
        $this->assertContains($results[1], ['User 0', 'User 1']);
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

    public function testGetStatementReturnsPDOStatement()
    {
        $stmt = $this->select
            ->setColumns()
            ->from('users')
            ->getStatement();

        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testErrorCodeCorrespondsToTheStatement()
    {
        $stmt = $this->insert
            ->into('users')
            ->values(['id' => 1])
            ->getStatement();

        $stmt->execute();
        $errorCode = $stmt->errorCode();

        $this->assertEquals(1, preg_match('/^23/', $errorCode));
    }
}
