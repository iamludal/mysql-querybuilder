<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use PDOStatement;
use PDO;

abstract class Clause
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var PDOStatement
     */
    protected $statement;

    /**
     * Create a new clause
     * 
     * @param PDO $pdo (optional) a PDO instance to fetch/execute the clause
     */
    public function __construct($pdo = null)
    {
        if (!is_null($pdo) && !($pdo instanceof PDO))
            throw new InvalidArgumentException('Constructor parameter should be a PDO instance');

        $this->pdo = $pdo;
    }


    /**
     * Validate the query
     * 
     * @throws InvalidQueryException if the query is invalid/incomplete
     */
    abstract protected function validate();

    /**
     * Convert the query into a SQL string
     * 
     * @return string the SQL string
     * @throws InvalidQueryException if the query is invalid/incomplete
     */
    abstract public function toSQL(): string;

    /**
     * Execute the current query
     * 
     * @param array $parameters (optional) the values to bind to the parameters,
     * as you would do it with PDO.
     * @return bool TRUE on success or FALSE on failure
     * @throws PDOException On error if PDO::ERRMODE_EXCEPTION option is true.
     * @throws InvalidQueryException if the query is invalid/incomplete
     * @see https://www.php.net/manual/en/pdostatement.execute.php
     */
    public function execute($values = null)
    {
        $sql = $this->toSQL();
        $this->statement = $this->pdo->prepare($sql);
        return $this->statement->execute($values);
    }

    /**
     * Fetch the first row returned by the execution of the query.
     * Parameters are the same as the `PDOStatement::fetch` ones
     * 
     * @see https://www.php.net/manual/en/pdostatement.fetch.php
     */
    public function fetch($fetch_style = null, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        $this->execute();
        return $this->statement
            ->fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }

    /**
     * Fetch all the rows returned by the execution of the query.
     * Parameters are the same as the `PDOStatement::fetchAll` ones
     * 
     * @see php.net/manual/en/pdostatement.fetchall.php
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = array())
    {
        $this->execute();
        return $this->statement
            ->fetchAll($fetch_style, $fetch_argument, $ctor_args);
    }
}
