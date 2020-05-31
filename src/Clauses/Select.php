<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\Clause;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Select extends Clause
{
    private $table; // the table from which to select
    private $columns = []; // columns to select
    private $conditions = []; // WHERE conditions
    private $order = []; // ORDER BY columns
    private $LIMIT; // the LIMIT
    private $OFFSET; // the OFFSET

    /**
     * Specify the columns to select.
     * 
     * Each column should be either a string, which is the name of the column,
     * or an associatve array of the form :
     *      [$column1 => $alias1, $column2 => $alias2, ...]
     * (where $columnX and $aliasX are strings)
     * 
     * @param string|array ...$columns (optional) the columns to select. Default: '*'
     * @return Select the instance
     */
    public function select(...$columns)
    {
        $this->columns = [];

        foreach ($columns as $column) {
            if (is_string($column))
                $this->addColumn($column);
            elseif (is_array($column)) {
                $this->addColumnsFromArray($column);
            } else
                throw new InvalidArgumentException("Argument should be a string or array");
        }

        if (!$columns)
            $this->addColumn('*');

        return $this;
    }

    /**
     * Specify the table from which to select
     * 
     * @param string $table the table name
     * @param string $alias (optional) the alias to give to the table
     * @return $this
     * @throws InvalidArgumentException if the table name is not a
     */
    public function from($table, $alias = null)
    {
        if (!is_string($table))
            throw new InvalidArgumentException('Table name should be a string');
        elseif (is_string($alias))
            $table = "$table AS $alias";

        $this->table = $table;

        return $this;
    }

    /**
     * Add a select condition (WHERE clause)
     * 
     * @param string[] ...$conditions the condition
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

    /**
     * Add ORDER clause to the query
     * 
     * @param string $column the column to select
     * @param string $direction (optional) the direction (ASC or DESC)
     * @return $this
     * @throws InvalidArgumentException if the direction is invalid
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = mb_strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC']))
            throw new InvalidArgumentException('Direction should be either ASC or DESC');

        $this->order[] = "$column $direction";

        return $this;
    }

    /**
     * Add the LIMIT of rows to select
     * 
     * If $limit is omitted, then $param1 correspond to the OFFSET.
     * Otherwise, $param1 corresponds to the LIMIT.
     * 
     * @param int $param1 either the LIMIT or the OFFSET (see docs)
     * @param int $limit (optional) the LIMIT;
     * @return $this
     * @throws InvalidArgumentException if LIMIT and/or OFFSET is (are) not int
     */
    public function limit($param1, $limit = null)
    {
        if (is_int($param1) && is_null($limit))
            $this->LIMIT = $param1;
        elseif (is_int($param1) && is_int($limit)) {
            $this->LIMIT = $limit;
            $this->OFFSET = $param1;
        } else {
            throw new InvalidArgumentException('LIMIT/OFFSET should be int');
        }

        return $this;
    }

    /**
     * Add the OFFSET
     * 
     * @param int $offset the offset
     * @return $this
     * @throws InvalidArgumentException if $offset is not an integer
     */
    public function offset($offset)
    {
        if (!is_int($offset))
            throw new InvalidArgumentException('OFFSET should be an int');

        $this->OFFSET = $offset;

        return $this;
    }

    /**
     * Add a column to the SELECT clause
     * 
     * @param string $columnName the name of the column to add
     * @param string $alias (optional) the alias to give to the column
     * @throws InvalidArgumentException if the name/alias is not valid
     */
    private function addColumn($columnName, $alias = null)
    {
        if (is_string($alias))
            $columnName = "$columnName AS $alias";

        $this->columns[] = $columnName;
    }

    /**
     * Add columns from an array of the form:
     *      [$column1, ..., $column2 => $alias2, ...]
     * (where $columnX and $aliasX are strings)
     */
    private function addColumnsFromArray($array)
    {
        foreach ($array as $key => $value)
            if (is_int($key))
                $this->addColumn($value);
            else
                $this->addColumn($key, $value);
    }

    protected function validate()
    {
        $conditions = [
            $this->table != null,
            count($this->columns) > 0
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException('Query is invalid/incomplete');
    }

    public function toSQL(): string
    {
        $this->validate();

        $columns = implode(', ', $this->columns);
        $sql = "SELECT $columns FROM {$this->table}";

        if ($this->conditions) {
            $conditions = implode(') OR (', $this->conditions);
            $sql .= " WHERE ($conditions)";
        }

        if ($this->order)
            $sql .= " ORDER BY " . implode(', ', $this->order);

        if ($this->LIMIT)
            $sql .= " LIMIT {$this->LIMIT}";

        if ($this->OFFSET)
            $sql .= " OFFSET {$this->OFFSET}";

        return $sql;
    }
}
