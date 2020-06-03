<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Delete extends Clause
{
    /**
     * @var string
     */
    private $table;

    /**
     * @param string[] WHERE conditions
     */
    private $conditions = [];

    /**
     * Set the table from which to delete rows
     * 
     * @param string $table the table
     * @return $this
     * @throws InvalidArgumentException if $table is not a string
     */
    public function from($table)
    {
        if (!is_string($table))
            throw new InvalidArgumentException('Table name should be a string');

        $this->table = $table;

        return $this;
    }

    /**
     * Add a select condition (WHERE clause)
     * 
     * @param string[] ...$conditions the conditions
     * @return $this
     * @throws InvalidArgumentException if any condition is not a string
     */
    public function where(...$conditions)
    {
        foreach ($conditions as $condition) {
            if (!is_string($condition))
                throw new InvalidArgumentException('Conditions must be strings');
        }

        if ($conditions)
            $this->conditions[] = implode(' AND ', $conditions);

        return $this;
    }

    /**
     * Add OR operator for WHERE clause.
     * 
     * @param array|string ...$conditions the conditions
     * @return $this
     * @throws InvalidArgumentException if any condition is not a string
     */
    public function orWhere(...$conditions)
    {
        $this->where(...$conditions);
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

        $sql = "DELETE FROM {$this->table}";

        if ($this->conditions) {
            $conditions = implode(') OR (', $this->conditions);
            $sql .= " WHERE ($conditions)";
        }

        return $sql;
    }
}
