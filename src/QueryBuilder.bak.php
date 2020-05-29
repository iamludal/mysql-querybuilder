<?php

namespace Ludal\QueryBuilder;

use Ludal\QueryBuilder\Clauses\Select;
use PDOStatement;
use Exception;
use PDO;

class QueryBuilder
{
    const DEFAULT_FETCH_MODE = PDO::FETCH_OBJ;

    private $pdo;
    private $action;
    private $columns = [];
    private $table;
    private $where = [];
    private $limit;
    private $offset;
    private $params = [];
    private $values = [];
    private $stmt;

    /**
     * Create a new QueryBuilder from an optional PDO statement (that will
     * allow to fetch/execute the queries directly)
     * 
     * @param PDO $pdo (optional) a PDO instance
     */
    public function __construct(PDO $pdo = null)
    {
        if ()
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
        return (new Select($this->pdo))->select(...$columns);
    }>

    /**
     * Correspond to the UPDATE sql clause
     * 
     * @param string $table the table to update
     */
    public function update(string $table): self
    {
        $this->action = self::UPDATE;
        $this->table = $table;

        return $this;
    }

    /**
     * Correspond to the sql DELETE clause
     * 
     * @param string $table the table from which to delete rows
     */
    public function deleteFrom(string $table): self
    {
        $this->action = self::DELETE;
        $this->from($table);

        return $this;
    }

    /**
     * Correspond to the sql INSERT INTO clause
     * 
     * @param string $table the table in which to insert rows
     */
    public function insertInto(string $table): self
    {
        $this->action = self::INSERT;
        $this->table = $table;

        return $this;
    }

    /**
     * Add a value for the INSERT INTO query
     * 
     * @param array $row an associative array corresponding to the row to add
     * (ex: ["name" => "John", "age" => 23])
     */
    private function addValue(array $row)
    {
        $this->values[] = $row;
        $row = array_values($row);
        $nCols = count($row);
        $nbParams = count($this->params);

        for ($i = 0; $i < $nCols; $i++)
            $this->setParam($nbParams + $i + 1, $row[$i]);
    }

    /**
     * Correspond to the sql VALUES clause that follows INSERT INTO
     * 
     * @param array $values a bidimensional array containg all the values to
     * insert, one value being an associative array containing the column names
     * as keys, and the values to insert as values.
     */
    public function values(array $values): self
    {
        foreach ($values as $value)
            $this->addValue($value);

        if ($values)
            $this->columns = array_keys($values[0]);

        return $this;
    }

    /**
     * Correspond to the sql SET clause of an UPDATE stmt
     * 
     * @param string $column the column to change
     * @param mixed $value the value to the for the selected column
     */
    public function set(string $column, $value): self
    {
        $this->columns[] = $column;
        $this->values[] = $value;

        return $this;
    }

    /**
     * Convert a DELETE sql command into a string
     */
    public function deleteToSQL(): string
    {
        $sql = "DELETE FROM {$this->table}";

        if ($this->where)
            $sql .= " {$this->whereToSQL()}";

        if ($this->limit)
            $sql .= " LIMIT " . $this->limit;

        if ($this->offset)
            $sql .= " OFFSET " . $this->offset;

        return $sql;
    }

    /**
     * Convert an INSERT INTO sql command into a string
     */
    public function insertToSQL(): string
    {
        $sql = "INSERT INTO {$this->table}";

        $sql .= " (" . implode(", ", $this->columns) . ")";

        // number of rows to insert
        $nRows = count($this->values);

        // number of columns for each row
        $cols = $nRows > 0 ? count($this->values[0]) : 0;

        $parts = str_split(str_repeat("?", $cols)); // ['?', '?', '?']
        $row = implode(", ", $parts); // "?, ?, ?"

        $rows = [];

        for ($i = 0; $i < $nRows; $i++)
            $rows[] = $row;

        $sql .= " VALUES (" . implode("), (", $rows) . ")";

        return $sql;
    }

    /**
     * Convert an UPDATE sql command into a string
     */
    public function updateToSQL(): string
    {
        $sql = "UPDATE {$this->table} SET ";

        $nCols = count($this->columns);

        // ["?", "?", "?", ...] -> $nCols times
        $values = array_map(function ($column) {
            return "$column = ?";
        }, $this->columns);

        $sql .= "(" . implode(", ", $values) . ")";

        if ($this->where)
            $sql .= " {$this->whereToSQL()}";

        if ($this->limit)
            $sql .= " LIMIT " . $this->limit;

        if ($this->offset)
            $sql .= " OFFSET " . $this->limit;

        return $sql;
    }

    /**
     * Get the current SQL query as a string
     * 
     * @return string the current SQL query
     */
    public function toSQL(): string
    {
        switch ($this->action) {
            case self::SELECT:
                return $this->selectToSQL();
            case self::DELETE:
                return $this->deleteToSQL();
            case self::INSERT:
                return $this->insertToSQL();
            case self::UPDATE:
                return $this->updateToSQL();
        }
    }

    /**
     * Get the corresponding PDO param type depending on the type of the
     * value passed as parameter (ex: 'string' => PDO::PARAM_STR,
     * 'integer' => PDO::PARAM_INT ...)
     * 
     * @param mixed $value the value to get the PDO param for
     * @return int the corresponding PDO param
     */
    public static function getPDOType($value)
    {
        switch (gettype($value)) {
            case "string":
            case "double":
                return PDO::PARAM_STR;
            case "boolean":
                return PDO::PARAM_BOOL;
            case "integer":
                return PDO::PARAM_INT;
            case "NULL":
                return PDO::PARAM_NULL;
            case "resource":
                return PDO::PARAM_LOB;
            case "array":
            case "object":
                throw new Exception("Incorrect type");
            default:
                throw new Exception("Unknown type, please set it explicitly");
        }
    }

    /**
     * Set a param value of a prepared statement
     * 
     * @param string $name the name of the param to set the value for
     * @param $value the value to set for the param
     * @param int $type (optional) the type of the param (ex: PDO::PARAM_STR)
     * {@link https://www.php.net/manual/en/pdo.constants.php PDO params types}
     * If $type is missing, the builder will try to guess it
     */
    public function setParam($name, $value, $type = null)
    {
        if (!$type)
            $type = self::getPDOType($value);

        $this->params[] = compact('name', 'value', 'type');

        return $this;
    }

    /**
     * Set values for multiple params
     * 
     * @param array $params an array associating the name of each param to its
     * value. Eg: [':city' => 'NY', ':id' => 5]
     * @param int $type (optional) the type for the params. If $type is missing,
     * the builder will try to guess the type of the param
     */
    public function setParams(array $params, $type = null)
    {
        foreach ($params as $name => $value)
            $this->setParam($name, $value, $type);

        return $this;
    }

    /**
     * Execute the current SQL query and return the statement to fetch it later
     * 
     * @return PDOStatement the pdo statement
     */
    public function execute()
    {
        if ($this->pdo === null)
            throw new Exception("Can't fetch without a pdo instance");

        $this->stmt = $this->pdo->prepare($this->toSQL());

        foreach ($this->params as $param) {
            extract($param);
            $this->stmt->bindValue($name, $value, $type);
        }

        return $this->stmt->execute();
    }

    /**
     * Return the number of columns affected by the last query
     * 
     * @return int the number of columns
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Fetch a single
     */
    public function fetch($fetchMode = self::DEFAULT_FETCH_MODE, $class = null)
    {
        $this->execute();

        if ($fetchMode == PDO::FETCH_CLASS && $class != null)
            $this->stmt->setFetchMode($fetchMode, $class);
        else
            $this->stmt->setFetchMode($fetchMode);

        return $this->stmt->fetch();
    }

    /**
     * Fetch all the results corresponding to the current SQL query
     * 
     * @return array the results
     */
    public function fetchAll($fetchMode = self::DEFAULT_FETCH_MODE, $class = null)
    {
        $this->execute();

        if ($fetchMode == PDO::FETCH_CLASS && $class != null)
            return $this->stmt->fetchAll($fetchMode, $class);

        return $this->stmt->fetchAll($fetchMode);
    }
}
