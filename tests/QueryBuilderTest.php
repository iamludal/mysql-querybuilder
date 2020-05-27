<?php

use PHPUnit\Framework\TestCase;
use Core\QueryBuilder;
use phpDocumentor\Reflection\Types\Object_;

final class QueryBuilderTest extends TestCase
{
    public function getBuilder()
    {
        return new QueryBuilder($this->getPDO());
    }

    public function getPDO()
    {
        $pdo = new PDO("sqlite::memory:");

        $pdo->query('CREATE TABLE products (
            id INTEGER CONSTRAINT products_pk primary key autoincrement,
            name TEXT,
            address TEXT,
            city TEXT)');

        for ($i = 1; $i <= 10; $i++)
            $pdo->exec("INSERT INTO products (name, address, city) VALUES ('Product $i', 'Address $i', 'City $i');");

        $pdo->exec("UPDATE products SET city = NULL WHERE id = 9");

        return $pdo;
    }

    public function testSimpleQuery()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("products")
            ->toSQL();

        $this->assertEquals("SELECT * FROM products", $sql);
    }

    public function testSimpleQueryWithGivenColumns()
    {
        $sql = $this->getBuilder()
            ->select("name", "id")
            ->from("products")
            ->toSQL();

        $this->assertEquals("SELECT name, id FROM products", $sql);
    }

    public function testSimpleQueryWithSelectAsArray()
    {
        $sql = $this->getBuilder()
            ->select(["name", "id"])
            ->from("products")
            ->toSQL();

        $this->assertEquals("SELECT name, id FROM products", $sql);
    }

    public function testSimpleQueryWithTableAlias()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("users", "u")
            ->toSQL();

        $this->assertEquals("SELECT * FROM users u", $sql);
    }

    public function testSelectQueryWithOneWhereClause()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("products", "p")
            ->where("id = 5")
            ->toSQL();

        $this->assertEquals("SELECT * FROM products p WHERE (id = 5)", $sql);
    }

    public function testSelectQueryWithMultipleWhereClauses()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("posts")
            ->where("id = 5", "year < 2010")
            ->toSQL();

        $this->assertEquals("SELECT * FROM posts WHERE (id = 5 AND year < 2010)", $sql);
    }

    public function testSelectQueryWithWhereOrClause()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("id = 5")
            ->orWhere("views < 100")
            ->toSQL();

        $expected = "SELECT * FROM products WHERE (id = 5) OR (views < 100)";

        $this->assertEquals($expected, $sql);
    }

    public function testSelectQueryWithMultipleWhereAndOrClauses()
    {
        $sql = $this->getBuilder()
            ->select("*")
            ->from("users")
            ->where("id = 10", "age = 18")
            ->orWhere("city = 'Lille'", "country = 'FR'")
            ->toSQL();

        $expected = "SELECT * FROM users WHERE (id = 10 AND age = 18) OR (city = 'Lille' AND country = 'FR')";

        $this->assertEquals($expected, $sql);
    }

    public function testSelectQueryOneOrderBy()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("users")
            ->orderBy("id", "DESC")
            ->toSQL();

        $expected = "SELECT * FROM users ORDER BY id DESC";

        $this->assertEquals($expected, $sql);
    }

    public function testSelectQueryMultipleOrderBy()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("users")
            ->orderBy("age", "asc")
            ->orderBy("name", "desc")
            ->toSQL();

        $expected = "SELECT * FROM users ORDER BY age ASC, name DESC";

        $this->assertEquals($expected, $sql);
    }

    public function testSelectQueryWithLimit()
    {
        $sql = $this->getBuilder()
            ->select()
            ->from("posts")
            ->limit(10)
            ->toSQL();

        $expected = "SELECT * FROM posts LIMIT 10";

        $this->assertEquals($expected, $sql);
    }

    public function testSelectQueryWithLimitAndOffset()
    {
        $sql1 = $this->getBuilder()
            ->select()
            ->from("users")
            ->limit(5, 10)
            ->toSQL();

        $sql2 = $this->getBuilder()
            ->select()
            ->from("users")
            ->limit(10)
            ->offset(5)
            ->toSQL();

        $expected = "SELECT * FROM users LIMIT 10 OFFSET 5";

        $this->assertEquals($expected, $sql1);
        $this->assertEquals($sql2, $sql1);
    }

    public function testComplexQuery()
    {
        $sql = $this->getBuilder()
            ->select("name", "gender")
            ->from("users", "u")
            ->where("age >= 18", "age <= 25")
            ->orWhere("age > 65")
            ->orderBy("age")
            ->orderBy("id", "desc")
            ->limit(5, 10)
            ->toSQL();

        $expected = "SELECT name, gender FROM users u WHERE (age >= 18 AND age <= 25) OR (age > 65) ORDER BY age ASC, id DESC LIMIT 10 OFFSET 5";

        $this->assertEquals($expected, $sql);
    }

    public function testFetchRowExists()
    {
        $result = $this->getBuilder()
            ->select("city")
            ->from("products")
            ->where("id = 6")
            ->fetch();

        $this->assertEquals("City 6", $result->city);
    }

    public function testFetchWithFetchModeClass()
    {
        $result = $this->getBuilder()
            ->select("city")
            ->from("products")
            ->where("id = 6")
            ->fetch(PDO::FETCH_CLASS, stdClass::class);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertEquals("City 6", $result->city);
    }

    public function testFetchWithFetchModeAssoc()
    {
        $result = $this->getBuilder()
            ->select("city")
            ->from("products")
            ->where("id = 6")
            ->fetch(PDO::FETCH_ASSOC);

        $this->assertIsArray($result);
        $this->assertEquals("City 6", $result['city']);
    }

    public function testFetchRowDoesntExist()
    {
        $result = $this->getBuilder()
            ->select("city")
            ->from("products")
            ->where("id = 15")
            ->fetch();

        $this->assertFalse($result);
    }

    public function testFetchWithoutPdoInstance()
    {
        $this->expectException(Exception::class);

        (new QueryBuilder())
            ->select()
            ->from("users")
            ->fetch();
    }

    public function testFetchAllWhenRowsExist()
    {
        $products = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("id < 5")
            ->fetchAll();

        $this->assertCount(4, $products);

        for ($i = 1; $i < 5; $i++)
            $this->assertTrue($products[$i - 1]->address === "Address $i");
    }

    public function testFetchAllWithFetchModeAssoc()
    {
        $results = $this->getBuilder()
            ->select()
            ->from("products")
            ->fetchAll(PDO::FETCH_ASSOC);

        $this->assertIsArray($results);
    }

    public function testFetchAllWithFetchModeClass()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->fetchAll(PDO::FETCH_CLASS, stdClass::class);

        $this->assertContainsOnlyInstancesOf(stdClass::class, $result);
    }

    public function testFetchAllWithNoExistingRow()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("id < 0")
            ->fetchAll();

        $this->assertEmpty($result);
    }

    public function testSetParamInt()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("id = :id")
            ->setParam(":id", 5, PDO::PARAM_INT)
            ->fetch();

        $expected = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("id = 5")
            ->fetch();

        $this->assertEquals($expected, $result);
    }

    public function testSetParamManually()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = :city")
            ->orderBy('id')
            ->setParam(':city', 'City 3', PDO::PARAM_STR)
            ->fetchAll();

        $expected = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = 'City 3'")
            ->orderBy('id')
            ->fetchAll();

        $this->assertEquals($expected, $result);
    }

    public function testSetParamWithStr()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = :city")
            ->orderBy('id')
            ->setParam(':city', 'City 3')
            ->fetchAll();

        $expected = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = 'City 3'")
            ->orderBy('id')
            ->fetchAll();

        $this->assertEquals($expected, $result);
    }

    public function testSetParamWithNull()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city is :city")
            ->setParam(":city", null)
            ->fetch();

        $expected = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city is null")
            ->fetch();

        $this->assertEquals($expected, $result);
    }

    public function testMultipleSetParam()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = :city")
            ->orWhere("id = :id")
            ->setParam(":city", "City 2")
            ->setParam(":id", 5)
            ->fetchAll();

        $expected = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = 'City 2'")
            ->orWhere("id = 5")
            ->fetchAll();

        $this->assertEquals($expected, $result);
    }

    public function testSetParamsWithParamsOfDifferentTypes()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = :city")
            ->orWhere("id = :id")
            ->setParams([":city" => "City 2", ":id" => 4])
            ->fetchAll();

        $expected = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = 'City 2'")
            ->orWhere("id = 4")
            ->fetchAll();

        $this->assertEquals($expected, $result);
    }

    public function testSetParamsWithParamsOfSameType()
    {
        $result = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = :city")
            ->orWhere("address = :address")
            ->setParams(
                [":city" => "City 2", ":address" => "Address 3"],
                PDO::PARAM_STR
            )
            ->fetchAll();

        $expected = $this->getBuilder()
            ->select()
            ->from("products")
            ->where("city = 'City 2'")
            ->orWhere("address = 'Address 3'")
            ->fetchAll();

        $this->assertEquals($expected, $result);
    }

    public function testGetPDOType()
    {

        $builder = $this->getBuilder();
        $file = fopen("tmp", "w");

        $this->assertEquals(PDO::PARAM_INT, $builder::getPDOType(5));
        $this->assertEquals(PDO::PARAM_STR, $builder::getPDOType(5.1));
        $this->assertEquals(PDO::PARAM_STR, $builder::getPDOType("test"));
        $this->assertEquals(PDO::PARAM_BOOL, $builder::getPDOType(true));
        $this->assertEquals(PDO::PARAM_LOB, $builder::getPDOType($file));

        fclose($file);
        unlink("tmp");
    }

    public function testGetPDOTypeThrowErrors()
    {
        $builder = $this->getBuilder();
        $params = [array(), (object) 5];

        foreach ($params as $param) {
            $this->expectException(Exception::class);
            $builder::getPDOType($param);
        }
    }

    public function testDeleteQuery()
    {
        $sql = $this->getBuilder()
            ->deleteFrom('articles')
            ->where('id = 10')
            ->limit(100)
            ->offset(5)
            ->toSQL();

        $expected = "DELETE FROM articles WHERE (id = 10) LIMIT 100 OFFSET 5";

        $this->assertEquals($expected, $sql);
    }

    public function testInsertQuery()
    {
        $sql = $this->getBuilder()
            ->insertInto('articles')
            ->values([
                ['id' => 3, 'title' => "He's here!"],
                ['id' => 4, 'title' => "Big Update!"],
                ['id' => 5, 'title' => ""]
            ])
            ->toSQL();

        $expected = "INSERT INTO articles (id, title) VALUES (?, ?), (?, ?), (?, ?)";

        $this->assertEquals($expected, $sql);
    }

    public function testInsertRealQuery()
    {
        $pdo = $this->getPDO();

        $results = (new QueryBuilder($pdo))
            ->select()
            ->from("products")
            ->where("id > 10")
            ->fetchAll();

        $this->assertCount(0, $results);

        $stmt = (new QueryBuilder($pdo))
            ->insertInto('products')
            ->values([
                ['name' => "Product 11", 'address' => "Address 11", 'city' => "City 11"],
                ['name' => "Product 12", 'address' => "Address 12", 'city' => "City 12"],
            ]);

        $expected = "INSERT INTO products (name, address, city) VALUES (?, ?, ?), (?, ?, ?)";

        $this->assertEquals($expected, $stmt->toSQL());

        $stmt->execute();

        // verify that the values has been inserted

        $results = (new QueryBuilder($pdo))
            ->select()
            ->from("products")
            ->where("id > 10")
            ->fetchAll();

        $this->assertCount(2, $results);

        $list = ['name' => 'Product', 'city' => 'City', 'address' => 'Address'];

        foreach ($list as $key => $value)
            $this->assertEquals($results[0]->$key, "$value 11");
        foreach ($list as $key => $value)
            $this->assertEquals($results[1]->$key, "$value 12");
    }

    public function testInsertQueryEmptyValues()
    {
        $sql = $this->getBuilder()
            ->insertInto('articles')
            ->values([])
            ->toSQL();

        $expected = "INSERT INTO articles () VALUES ()";

        $this->assertEquals($expected, $sql);
    }

    public function testUpdateStatement()
    {
        $sql = $this->getBuilder()
            ->update('articles')
            ->set('name', 'John')
            ->set('age', 20)
            ->where('id = 15')
            ->orWhere('name is NULL')
            ->limit(100)
            ->toSQL();

        $expected = "UPDATE articles SET (name = ?, age = ?) WHERE (id = 15) OR (name is NULL) LIMIT 100";

        $this->assertEquals($expected, $sql);
    }

    public function testRowCount()
    {
        $stmt = $this->getBuilder();

        $stmt
            ->insertInto('products')
            ->values([
                ['name' => 'p1', 'city' => 'c1', 'address' => 'a1'],
                ['name' => 'p2', 'city' => 'c2', 'address' => 'a2'],
                ['name' => 'p3', 'city' => 'c3', 'address' => 'a3'],
            ])
            ->execute();

        $this->assertEquals(3, $stmt->rowCount());
    }
}
