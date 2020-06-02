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
            ->toSQL();

        $expected = 'UPDATE users SET name = :v1, age = :v2';

        $this->assertEquals($expected, $sql);
    }

    public function testSimpleQueryWithWhere()
    {
        $sql = $this->builder
            ->setTable('users')
            ->set(['name' => 'Ludal', 'age' => 20])
            ->where('id = 8')
            ->toSQL();

        $expected = 'UPDATE users SET name = :v1, age = :v2 WHERE (id = 8)';

        $this->assertEquals($expected, $sql);
    }

    public function testQueryWithComplexConditions()
    {
        $sql = $this->builder
            ->setTable('cars')
            ->set(['price' => 1500])
            ->where('year < 2000', 'brand = "Peugeot"')
            ->orWhere('km > 100000')
            ->orWhere('color = "red"')
            ->toSQL();

        $expected = 'UPDATE cars SET price = :v1 ';
        $expected .= 'WHERE (year < 2000 AND brand = "Peugeot") ';
        $expected .= 'OR (km > 100000) OR (color = "red")';

        $this->assertEquals($expected, $sql);
    }
}
