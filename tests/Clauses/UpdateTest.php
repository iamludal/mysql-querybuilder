<?php

namespace Ludal\QueryBuilder\Tests\Clauses;

use Ludal\QueryBuilder\Clauses\Update;
use PHPUnit\Framework\TestCase;

final class UpdateTest extends TestCase
{
    /**
     * @var Update
     */
    private $builder;

    public function setUp(): void
    {
        $this->builder = new Update();
    }

    public function testSimpleQuery()
    {
        $sql = $this->builder
            ->setTable('users')
            ->set(['name' => 'Ludal', 'age' => 20])
            ->where('id = 8')
            ->toSQL();

        $expected = 'UPDATE users SET name = :v1, age = :v2 WHERE (id = 8)';

        $this->assertEquals($expected, $sql);
    }
}
