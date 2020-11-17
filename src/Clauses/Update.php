<?php

namespace Ludal\QueryBuilder\Clauses;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use InvalidArgumentException;
use Ludal\QueryBuilder\Utils;

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
     * @var string[] the params, in the form : "id = 4", "age = 20"...
     */
    private $updateParams = [];

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
     * @param mixed[]|string ...$values an associative array of the form
     * [$col => $val, ...] or a string, that is directly the value to set,
     * for instance: "id = 5"
     * @return $this
     * @throws InvalidArgumentException if $values is neither an associative
     * array nor a string
     */
    public function set(...$values)
    {
        foreach ($values as $value)
            if (is_string($value))
                $this->updateParams[] = $value;
            elseif (is_array($value))
                foreach ($value as $key => $val)
                    $this->setValue($key, $val);

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

        $this->updateParams[] = "$column = :$column";
        $this->params[":$column"] = $value;

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
            is_string($this->table),
            count($this->params + $this->updateParams) > 0,
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException('Query is invalid or incomplete');
    }

    public function toSQL(): string
    {
        $this->validate();

        $sql = "UPDATE {$this->table} SET ";

        $sql .= implode(', ', $this->updateParams);

        if ($this->conditions) {
            $conditions = implode(') OR (', $this->conditions);
            $sql .= " WHERE ($conditions)";
        }

        return $sql;
    }
}
