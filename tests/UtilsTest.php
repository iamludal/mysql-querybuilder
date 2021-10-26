<?php

namespace Ludal\QueryBuilder\Tests;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use Ludal\QueryBuilder\Utils;
use stdClass;
use PDO;

final class UtilsTest extends TestCase
{
    public function testInt()
    {
        $type = Utils::getPDOType(5);
        $this->assertEquals(PDO::PARAM_INT, $type);
    }

    public function testBool()
    {
        $type = Utils::getPDOType(false);
        $this->assertEquals(PDO::PARAM_BOOL, $type);
    }

    public function testString()
    {
        $type = Utils::getPDOType("string");
        $this->assertEquals(PDO::PARAM_STR, $type);

        $type = Utils::getPDOType("0");
        $this->assertEquals(PDO::PARAM_STR, $type);
    }

    public function testNull()
    {
        $type = Utils::getPDOType(null);

        $this->assertEquals(PDO::PARAM_NULL, $type);
    }

    public function testBlob()
    {
        $res = tmpfile();
        $type = Utils::getPDOType($res);
        fclose($res);
        $this->assertEquals(PDO::PARAM_LOB, $type);
    }

    public function testDouble()
    {
        $type = Utils::getPDOType(3.14);
        $this->assertEquals(PDO::PARAM_STR, $type);
    }

    public function testArrayThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Utils::getPDOType([]);
    }

    public function testObjectThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        Utils::getPDOType(new stdClass());
    }

    public function notAssociativeArrays(): array
    {
        return [
            [3],
            ["Hello"],
            [new stdClass()],
            [[1, 2, 3]]
        ];
    }

    /**
     * @dataProvider notAssociativeArrays
     */
    public function testIsNotAssociativeArray($notAssociativeArray)
    {
        $this->assertFalse(Utils::isAssociativeArray($notAssociativeArray));
    }

    public function associativeArrays(): array
    {
        return [
            [[]],
            [["foo" => "bar"]]
        ];
    }

    /**
     * @dataProvider associativeArrays
     */
    public function testIsAssociativeArray($associativeArray)
    {
        $this->assertTrue(Utils::isAssociativeArray($associativeArray));
    }
}
