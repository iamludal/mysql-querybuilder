<?php

namespace Ludal\QueryBuilder\Clauses;

use Ludal\QueryBuilder\Clauses\Clause;
use InvalidArgumentException;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Update extends Clause
{

    /**
     * @var string
     */
    private $table;

    /**
     * @var string[] the conditions to verify
     */
    private $conditions = [];

    /**
     * @var int keeps track of the number of values to set (:v1, :v2...)
     */
    private $i = 1;

    /**
     * @var string[] keeps track of the values: ["col1 = :v1", ...]
     */
    private $values = [];

    /**
     * @var mixed[] params to set : [$param => $value, ...]
     */
    private $params = [];

    /**
     * Set the table to update
     * 
     * @param string $table the table
     * @return $this
     * @throws InvalidArgumentException if $table is not a string
     */
    public function setTable($table)
    {
        if (!is_string($table))
            throw new InvalidArgumentException('Table name should be a string');

        $this->table = $table;

        return $this;
    }

    /**
     * Set the values to update
     * 
     * @param mixed[] $values an associative array of the form [$col => $val, ...]
     * @return $this
     * @throws InvalidArgumentException if $values is not an associative array
     */
    public function set($values)
    {
        foreach ($values as $key => $value)
            $this->setValue($key, $value);

        return $this;
    }

    /**
     * Set a value for the column to be updated
     * 
     * @param string $column the column name
     * @param mixed $value the value to set
     * @return $this 
     * @throws InvalidArgumentException if $column is not a string
     */
    public function setValue($column, $value)
    {
        if (!is_string($column))
            throw new InvalidArgumentException('Column name should be a string');

        $param = ":v{$this->i}";
        $this->params[] = "$column = $param";
        $this->values[$param] = $value;

        $this->i++;

        return $this;
    }

    /**
     * Set the update conditions
     * 
     * @param string[] ...$conditions the conditions
     * @return $this
     * @throws InvalidArgumentException if any $condition is not a string
     */
    public function where(...$conditions)
    {
        foreach ($conditions as $condition)
            if (!is_string($condition))
                throw new InvalidArgumentException('Condition should be a string');

        if ($conditions)
            $this->conditions[] = implode(' AND ', $conditions);

        return $this;
    }

    /**
     * Add conditions to be joined with the previous ones with OR
     * 
     * @param string[] ...$conditions the conditions
     * @return $this
     * @throws InvalidArgumentException if any $condition is not a string
     */
    public function orWhere(...$conditions)
    {
        foreach ($conditions as $condition)
            if (!is_string($condition))
                throw new InvalidArgumentException('Condition should be a string');

        $this->conditions[] = implode(' AND ', $conditions);

        return $this;
    }

    public function validate()
    {
        $conditions = [
            is_string($this->table)
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException('Query is invalid or incomplete');
    }

    public function toSQL(): string
    {
        $sql = "UPDATE {$this->table} SET ";

        $sql .= implode(', ', $this->params);

        if ($this->conditions) {
            $conditions = implode(') OR (', $this->conditions);
            $sql .= " WHERE ($conditions)";
        }

        return $sql;
    }
}
