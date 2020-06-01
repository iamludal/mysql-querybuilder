<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Clause;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Utils;

class Insert extends Clause
{
    /**
     * @var string the table in which to insert values
     */
    private $table;

    /**
     * @var string[] the columns
     */
    private $columns = [];

    /**
     * @param mixed[] the values to insert
     */
    private $values = [];

    /**
     * @var mixed[] params to bind (PDO)
     */
    private $params = [];

    /**
     * Specify the table in which to insert values
     * 
     * @param string $table the table
     * @return $this
     * @throws InvalidArgumentException is $table is not a string
     */
    public function into($table)
    {
        if (!is_string($table))
            throw new InvalidArgumentException('Table name should be a string');

        $this->table = $table;

        return $this;
    }

    /**
     * Specify the row to insert in the table.
     * 
     * It should be of the form: [$column1 => $value1, $column2 => $value2, ...]
     * 
     * @param array row the row to insert
     * @return $this
     * @throws InvalidArgumentException
     */
    public function values($row)
    {
        if (array_values($row) == $row)
            throw new InvalidArgumentException('Value should be an associative array');

        $this->columns = array_keys($row);
        $this->values = array_values($row);

        $i = 1;

        foreach ($row as $value) {
            $key = ":v{$i}";
            $this->params[$key] = $value;
            $i++;
        }

        return $this;
    }

    public function validate()
    {
        $conditions = [
            is_string($this->table) && mb_strlen($this->table) > 0,
            count($this->columns) > 0,
            count($this->values) > 0,
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException('Query is invalid or incomplete');
    }

    public function toSQL(): string
    {
        $this->validate();

        $table = $this->table;
        $columns = implode(', ', $this->columns);
        $params = implode(', ', array_keys($this->params));

        $sql = "INSERT INTO $table ($columns) VALUES ($params)";

        return $sql;
    }

    public function execute(...$args)
    {
        $sql = $this->toSQL();
        $this->statement = $this->pdo->prepare($sql);

        foreach ($this->params as $key => $value) {
            $type = Utils::getPDOType($value);
            $this->statement->bindValue($key, $value, $type);
        }

        return $this->statement->execute();
    }
}
