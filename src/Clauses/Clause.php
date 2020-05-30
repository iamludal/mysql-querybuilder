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
     * Set the PDO fetch mode. Works exactly the same as
     * PDOStatement::setFetchMode
     * 
     * @see https://www.php.net/manual/en/pdostatement.setfetchmode.php
     */
    public function setFetchMode(...$args)
    {
        if (is_null($this->statement))
            $this->createStatement();

        $this->statement->setFetchMode(...$args);
        return $this;
    }

    /**
     * Create a PDO statement from the current clause (sql)
     */
    public function createStatement()
    {
        $sql = $this->toSQL();
        $this->statement = $this->pdo->prepare($sql);
    }

    /**
     * Execute the current query. Works exactly the same as PDOStatement::execute
     * 
     * @return bool TRUE on success or FALSE on failure
     * @throws PDOException On error if PDO::ERRMODE_EXCEPTION option is true.
     * @throws InvalidQueryException if the query is invalid/incomplete
     * @see https://www.php.net/manual/en/pdostatement.execute.php
     */
    public function execute(...$args)
    {
        if (is_null($this->statement))
            $this->createStatement();

        return $this->statement->execute(...$args);
    }

    /**
     * Fetch the first row returned by the execution of the query.
     * Parameters are the same as the `PDOStatement::fetch` ones
     * 
     * @see https://www.php.net/manual/en/pdostatement.fetch.php
     */
    public function fetch(...$args)
    {
        if (is_null($this->statement))
            $this->execute();

        return $this->statement
            ->fetch(...$args);
    }

    /**
     * Fetch all the rows returned by the execution of the query.
     * Parameters are the same as the `PDOStatement::fetchAll` ones
     * 
     * @see php.net/manual/en/pdostatement.fetchall.php
     */
    public function fetchAll(...$args)
    {
        if (is_null($this->statement))
            $this->execute();

        return $this->statement
            ->fetchAll(...$args);
    }
}
