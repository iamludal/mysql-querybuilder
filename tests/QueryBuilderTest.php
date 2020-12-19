<?php

namespace Ludal\QueryBuilder\Tests;

use Ludal\QueryBuilder\Clauses\Insert;
use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\Clauses\Update;
use Ludal\QueryBuilder\Clauses\Delete;
use Ludal\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;
use PDO;

final class QueryBuilderTest extends TestCase
{
    /**
     * @var QueryBuilder
     */
    private $builder;

    public function setUp(): void
    {
        $this->builder = new QueryBuilder();
    }

    public function testSelectReturnsInstanceOfSelect()
    {
        $select = $this->builder->select();
        $this->assertInstanceOf(Select::class, $select);
    }

    public function testInsertReturnsInstanceOfInsert()
    {
        $res = $this->builder->insertInto('articles');
        $this->assertInstanceOf(Insert::class, $res);
    }

    public function testUpdateReturnsInstanceOfUpdate()
    {
        $update = $this->builder->update('users');
        $this->assertInstanceOf(Update::class, $update);
    }

    public function testDeleteReturnsInstanceOfDelete()
    {
        $res = $this->builder->deleteFrom('articles');
        $this->assertInstanceOf(Delete::class, $res);
    }
}
