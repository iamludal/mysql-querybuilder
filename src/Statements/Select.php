<?php

namespace Ludal\QueryBuilder\Statements;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\GroupBy;
use Ludal\QueryBuilder\Clauses\OrderBy;
use Ludal\QueryBuilder\Clauses\Where;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Select extends Statement
{
    use Where, GroupBy, OrderBy;

    /**
     * @var array the columns to select
     */
    private $columns = [];

    /**
     * @var int
     */
    private $LIMIT;

    /**
     * @var int
     */
    private $OFFSET;

    /**
     * Specify the columns to select.
     * 
     * Each column should be either a string, which is the name of the column,
     * or an associative array of the form:
     *      [$column1 => $alias1, $column2 => $alias2, ...]
     * (where $columnX and $aliasX are strings)
     * 
     * @param ...$columns (optional) the columns to select. Default: '*'
     * @return $this
     * @throws InvalidArgumentException if a column type is invalid
     */
    public function setColumns(...$columns): self
    {
        $this->columns = [];

        foreach ($columns as $column) {
            if (is_string($column))
                $this->addColumn($column);
            elseif (is_array($column)) {
                $this->addColumnsFromArray($column);
            } else
                throw new InvalidArgumentException('Argument should be a string or array');
        }

        if (!$columns)
            $this->addColumn('*');

        return $this;
    }

    /**
     * Add a column to the SELECT statement
     * 
     * @param string $columnName the name of the column to add
     * @param string|null $alias (optional) the alias to give to the column
     * @throws InvalidArgumentException if the name/alias is not valid
     */
    private function addColumn(string $columnName, string $alias = null): void
    {
        if ($alias)
            $columnName = "$columnName AS $alias";

        $this->columns[] = $columnName;
    }

    /**
     * Add columns from an array of the form:
     *      [$column1, ..., $column2 => $alias2, ...]
     * (where $columnX and $aliasX are strings)
     * 
     * @param array $columns the array of columns
     */
    private function addColumnsFromArray(array $columns): void
    {
        foreach ($columns as $key => $value)
            if (is_int($key))
                $this->addColumn($value);
            else
                $this->addColumn($key, $value);
    }

    /**
     * Specify the table from which to select
     * 
     * @param string $table the table name
     * @param string|null $alias (optional) the alias to give to the table
     * @return $this
     */
    public function from(string $table, string $alias = null): self
    {
        if ($alias)
            $table = "$table AS $alias";

        return $this->setTable($table);
    }

    /**
     * Add the LIMIT of rows to select
     * 
     * If $limit is omitted, then $param1 correspond to the OFFSET.
     * Otherwise, $param1 corresponds to the LIMIT.
     * 
     * @param int $param1 either the LIMIT or the OFFSET (see docs)
     * @param int|null $limit (optional) the LIMIT
     * @return $this
     */
    public function limit(int $param1, int $limit = null): self
    {
        if ($limit !== null) {
            $this->LIMIT = $limit;
            $this->OFFSET = $param1;
        } else {
            $this->LIMIT = $param1;
        }

        return $this;
    }

    /**
     * Add the OFFSET
     * 
     * @param int $offset the offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->OFFSET = $offset;
        return $this;
    }

    protected function validate(): void
    {
        $conditions = [
            $this->table != null,
            count($this->columns) > 0
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException();
    }

    public function toSQL(): string
    {
        $this->validate();

        $columns = implode(', ', $this->columns);
        $sql = "SELECT $columns FROM $this->table";

        if ($this->conditions)
            $sql .= ' ' . $this->whereToSQL();

        if ($this->groupByColumns)
            $sql .= ' ' . $this->groupByToSQL();

        if ($this->order)
            $sql .= ' ' . $this->orderByToSQL();

        if ($this->LIMIT)
            $sql .= " LIMIT $this->LIMIT";

        if ($this->OFFSET)
            $sql .= " OFFSET $this->OFFSET";

        return $sql;
    }
}
