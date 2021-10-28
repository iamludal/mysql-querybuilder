<?php

namespace Ludal\QueryBuilder\Tests\Clauses;

use Ludal\QueryBuilder\Tests\Mocks\OrderByMock;
use PHPUnit\Framework\TestCase;

final class OrderByTest extends TestCase
{
    /**
     * @var OrderByMock
     */
    private $orderByMock;

    public function setUp(): void
    {
        $this->orderByMock = new OrderByMock();
    }

    public function testOrderByAssociativeArray()
    {
        $actualSql = $this->orderByMock->orderBy(['id' => 'DESC', 'name' => 'ASC'])->toSQL();

        $expectedSql = 'ORDER BY id DESC, name ASC';

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testOrderByWithoutDirection()
    {
        $actualSql = $this->orderByMock->orderBy(['id'])->toSQL();

        $expectedSql = 'ORDER BY id';

        $this->assertEquals($expectedSql, $actualSql);
    }
}