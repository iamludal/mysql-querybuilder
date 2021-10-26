<?php

namespace Ludal\QueryBuilder;

use Ludal\QueryBuilder\Clauses\Delete;
use Ludal\QueryBuilder\Clauses\Insert;
use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\Clauses\Update;
use PDO;

class QueryBuilder
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * Create a new QueryBuilder from an optional PDO statement (that will
     * allow fetching/executing the queries directly)
     * 
     * @param PDO|null $pdo (optional) a PDO instance
     */
    public function __construct(PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Corresponds to the sql SELECT clause 
     * 
     * @param mixed ...$columns the columns to select
     * @return Select
     */
    public function select(...$columns): Select
    {
        return (new Select($this->pdo))->setColumns(...$columns);
    }

    /**
     * Corresponds to the sql INSERT INTO clause
     * 
     * @param string $table the table in which to insert values
     * @return Insert
     */
    public function insertInto(string $table): Insert
    {
        return (new Insert($this->pdo))->setTable($table);
    }

    /**
     * Corresponds to the sql UPDATE clause
     * 
     * @param string $table the table to update
     * @return Update
     */
    public function update(string $table): Update
    {
        return (new Update($this->pdo))->setTable($table);
    }

    /**
     * Corresponds to the sql DELETE FROM clause
     * 
     * @param string $table the table from which to delete rows
     * @return Delete
     */
    public function deleteFrom(string $table): Delete
    {
        return (new Delete($this->pdo))->setTable($table);
    }

    /**
     * Returns the last insert id.
     * 
     * @return string the id (as a string)
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
