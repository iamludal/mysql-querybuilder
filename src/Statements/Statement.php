<?php

namespace Ludal\QueryBuilder\Statements;

use BadMethodCallException;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Exceptions\UnknownType;
use Ludal\QueryBuilder\Utils;
use PDO;
use PDOException;
use PDOStatement;

abstract class Statement
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
     * @var bool
     */
    private $alreadyExecuted;

    /**
     * @var array PDO params to be bound
     */
    protected $params = [];

    /**
     * @var string The table name
     */
    protected $table;

    /**
     * Create a new statement
     *
     * @param PDO|null $pdo (optional) a PDO instance to fetch/execute the statement
     */
    public function __construct(PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }



    /**
     * Convert the query into an SQL string
     * 
     * @throws InvalidQueryException if the query is invalid/incomplete
     */
    abstract public function toSQL(): string;

    /**
     * Set the table on which to execute the query.
     * 
     * @param string $table the table name
     * @return $this
     */
    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the PDO fetch mode. Works exactly the same as
     * PDOStatement::setFetchMode
     *
     * @return $this
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if a param value has an unknown type
     * @see https://www.php.net/manual/en/pdostatement.setfetchmode.php
     */
    public function setFetchMode(...$args): self
    {
        if ($this->statement === null)
            $this->createStatement();

        $this->statement->setFetchMode(...$args);
        return $this;
    }

    /**
     * Bind a value to a prepared parameter
     *
     * @param string $param the name of the parameter
     * @param mixed $value the value to bind to the parameter
     * @param int|null $type (optional) the PDO type of the value (PDO::PARAM_INT, ...)
     * if omitted, the class will automatically detect the corresponding PDO
     * type of the value
     * @return $this
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if the value has an unknown type
     */
    public function setParam(string $param, $value, int $type = null): self
    {
        if (is_null($this->statement))
            $this->createStatement();

        $PDOType = is_null($type) ? Utils::getPDOType($value) : $type;

        $this->statement->bindParam($param, $value, $PDOType);
        return $this;
    }

    /**
     * Set multiple params at a time from an associative array that contains
     * params names as key and param values as values.
     *
     * PDO params types are automatically guessed by the class
     *
     * @param array $params params to set : [':param1' => $value1, ...]
     * @return $this
     * @throws BadMethodCallException if there is no PDO instance set
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if the value has an unknown type
     */
    public function setParams(array $params): self
    {
        foreach ($params as $key => $value)
            $this->setParam($key, $value);

        return $this;
    }

    /**
     * To bind a column to a specific types. Works exactly the same as the
     * PDOStatement::bindColumn method
     *
     * @param string $column the column to bind
     * @param mixed $var the variable that will receive the value
     * @param mixed ...$args other args for the PDO bindColumn method
     * @return $this
     * @throws PDOException if there is a PDO exception
     * @throws BadMethodCallException if there is no PDO instance
     * @throws InvalidQueryException
     * @throws UnknownType if a param value has an unknown type
     */
    public function bindColumn(string $column, &$var, ...$args): self
    {
        if ($this->statement === null)
            $this->createStatement();

        $this->statement->bindColumn($column, $var, ...$args);

        return $this;
    }

    /**
     * Return the number of rows affected by the execution of the query.
     *
     * You can call this method directly on the builder : if the query has
     * not been executed yet, it will execute it automatically
     *
     * @return int the row count
     * @throws BadMethodCallException if there is no PDO instance
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if a param value has an unknown type
     */
    public function rowCount(): int
    {
        if (!$this->alreadyExecuted)
            $this->execute();

        return $this->statement->rowCount();
    }

    /**
     * Create a PDO statement from the current statement (sql)
     *
     * @throws BadMethodCallException if there is no PDO instance
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType a param value has an unknown type
     */
    protected function createStatement(): void
    {
        if ($this->pdo === null)
            throw new BadMethodCallException('No PDO instance specified');

        $sql = $this->toSQL();
        $this->statement = $this->pdo->prepare($sql);

        // map each "key" to ":_key"
        foreach ($this->params as $key => $value) {
            $this->params[":_$key"] = $value;
            unset($this->params[$key]);
        }

        $this->setParams($this->params);
    }

    /**
     * Get the current PDO statement. If it doesn't exist, create it.
     *
     * @return PDOStatement the PDO statement
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if a param value has an unknown type
     */
    public function getStatement(): PDOStatement
    {
        if (!$this->statement)
            $this->createStatement();

        return $this->statement;
    }

    /**
     * Execute the current query. Works exactly the same as PDOStatement::execute
     *
     * @return bool the return value of PDOStatement::execute
     * @throws PDOException On error if PDO::ERRMODE_EXCEPTION option is true.
     * @throws InvalidQueryException if the query is invalid/incomplete
     * @throws BadMethodCallException if there is no PDO instance
     * @throws UnknownType if a param values has an unknown type
     * @see https://www.php.net/manual/en/pdostatement.execute.php
     */
    public function execute(...$args): bool
    {
        if ($this->pdo === null)
            throw new BadMethodCallException('Cannot execute without a PDO instance');

        $this->alreadyExecuted = true;

        return $this->getStatement()->execute(...$args);
    }

    /**
     * Fetch the first row returned by the execution of the query.
     * Parameters are the same as the `PDOStatement::fetch` ones
     *
     * @return mixed the return value of PDOStatement::fetch
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if a param value has an unknown type
     * @see https://www.php.net/manual/en/pdostatement.fetch.php
     */
    public function fetch(...$args)
    {
        if (!$this->alreadyExecuted)
            $this->execute();

        return $this->statement
            ->fetch(...$args);
    }

    /**
     * Fetch all the rows returned by the execution of the query.
     * Parameters are the same as the `PDOStatement::fetchAll` ones
     *
     * @return mixed the return value of PDOStatement::fetchAll
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if a param value has an unknown type
     * @see php.net/manual/en/pdostatement.fetchall.php
     */
    public function fetchAll(...$args): array
    {
        if (!$this->alreadyExecuted)
            $this->execute();

        return $this->statement
            ->fetchAll(...$args);
    }
}
