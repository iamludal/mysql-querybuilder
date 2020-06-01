<?php

namespace Ludal\QueryBuilder;

use Ludal\QueryBuilder\Clauses\Select;
use Ludal\QueryBuilder\Clauses\Insert;
use InvalidArgumentException;
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
}
