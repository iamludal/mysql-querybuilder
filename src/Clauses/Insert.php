<?php

namespace Ludal\QueryBuilder\Clauses;

use BadMethodCallException;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use InvalidArgumentException;

class Insert extends Clause
{
    /**
     * @var string the table in which to insert values
     */
    private $table;

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

        $this->params = $row;

        return $this;
    }

    public function validate()
    {
        $conditions = [
            is_string($this->table),
            count($this->params) > 0,
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException('Query is invalid or incomplete');
    }

    public function toSQL(): string
    {
        $this->validate();

        $table = $this->table;
        $cols = array_keys($this->params);
        $columns = implode(', ', $cols);
        $keys = array_map(function ($elt) {
            return ":$elt";
        }, $cols);
        $params = implode(', ', $keys);

        $sql = "INSERT INTO $table ($columns) VALUES ($params)";

        return $sql;
    }
}
