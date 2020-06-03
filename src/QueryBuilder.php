<?php

namespace Ludal\QueryBuilder;

use Ludal\QueryBuilder\Clauses\Clause;
use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\Clauses\Insert;
use Ludal\QueryBuilder\Clauses\Update;
use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Delete;
use PDO;

class QueryBuilder
{
    private $pdo;

    /**
     * Create a new QueryBuilder from an optional PDO statement (that will
     * allow to fetch/execute the queries directly)
     * 
     * @param PDO $pdo (optional) a PDO instance
     * @throws InvalidArgumentException if $pdo is not a PDO instance
     */
    public function __construct($pdo = null)
    {
        if (!is_null($pdo) && !($pdo instanceof PDO))
            throw new InvalidArgumentException('Argument should be a PDO instance');

        $this->pdo = $pdo;
    }

    /**
     * Corresponds to the sql SELECT clause 
     * 
     * @param string|array ...$columns the columns to select
     * @return Select
     */
    public function select(...$columns)
    {
        return (new Select($this->pdo))->setColumns(...$columns);
    }

    /**
     * Corresponds to the sql INSERT INTO clause
     * 
     * @param string $table the table in which to insert values
     * @return Insert
     * @throws InvalidArgumentException if $table is not a string
     */
    public function insertInto($table)
    {
        return (new Insert($this->pdo))->into($table);
    }

    /**
     * Corresponds to the sql UPDATE clause
     * 
     * @param string $table the table to update
     * @return Update
     * @throws InvalidArgumentException if $table is not a string
     */
    public function update($table)
    {
        return (new Update($this->pdo))->setTable($table);
    }

    /**
     * Corresponds to the sql DELETE FROM clause
     * 
     * @param string $table the table from which to delete rows
     * @return Delete
     * @throws InvalidArgumentException if $table is not a string
     */
    public function deleteFrom($table)
    {
        return (new Delete($this->pdo))->from($table);
    }

    /**
     * Set the PDO fetch mode. Works exactly the same as
     * PDOStatement::setFetchMode
     * 
     * @see https://www.php.net/manual/en/pdostatement.setfetchmode.php
     */
    public static function setDefaultFetchMode(...$fetchArgs)
    {
        Clause::setDefaultFetchMode(...$fetchArgs);
    }
}
