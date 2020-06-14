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
     * @var int keeps track of the number of values to set (:v1, :v2...)
     */
    private $count = 1;

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
     * @param mixed[]|string ...$values an associative array of the form
     * [$col => $val, ...] or a string, that is directly the value to set,
     * for instace: "id = 5"
     * @return $this
     * @throws InvalidArgumentException if $values is neither an associative
     * array nor a string
     */
    public function set(...$values)
    {
        foreach ($values as $value)
            if (is_string($value))
                $this->params[] = $value;
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

        $param = ":v{$this->count}";
        $this->params[] = "$column = $param";
        $this->values[$param] = $value;

        $this->count++;

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
        $this->validate();

        $sql = "UPDATE {$this->table} SET ";

        $sql .= implode(', ', $this->params);

        if ($this->conditions) {
            $conditions = implode(') OR (', $this->conditions);
            $sql .= " WHERE ($conditions)";
        }

        return $sql;
    }

    public function execute(...$args)
    {
        $sql = $this->toSQL();

        if ($this->statement === null)
            $this->createStatement();

        foreach ($this->values as $key => $value) {
            $type = Utils::getPDOType($value);
            $this->statement->bindValue($key, $value, $type);
        }

        return $this->statement->execute();
    }
}
