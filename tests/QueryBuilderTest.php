<?php

namespace Ludal\QueryBuilder\Tests;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;
use stdClass;

final class QueryBuilderTest extends TestCase
{
    private $builder;

    /**
     * @before
     */
    public function getBuilder()
    {
        $this->builder = new QueryBuilder();
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
}
