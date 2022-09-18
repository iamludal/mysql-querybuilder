<?php

namespace Ludal\QueryBuilder\Statements;

use InvalidArgumentException;
use Ludal\QueryBuilder\Clauses\GroupBy;
use Ludal\QueryBuilder\Clauses\Limit;
use Ludal\QueryBuilder\Clauses\OrderBy;
use Ludal\QueryBuilder\Clauses\Where;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Select extends Statement
{
    use Where, GroupBy, OrderBy, Limit;

    /**
     * @var array the columns to select
     */
    private $columns = [];

    /**
     * @var int
     */
    private $_offset;

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
        // declare empty array for columns
        $this->columns = [];

        foreach ($columns as $column) {
            if (is_string($column))
                $this->addColumn($column);

            elseif (is_array($column)) {
                $this->addColumnsFromArray($column);
            }
            else
                throw new InvalidArgumentException('Argument should be a string or array');
        }

        if (empty($columns))
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

        // add column name to columns table
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
    public function from(string $table , string $alias = null): self
    {
        if ($alias)
            $table = "$table AS $alias";

        return $this->setTable($table);
    }

    /**
     * Add the OFFSET
     *
     * @param int $offset the offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->_offset = $offset;
        return $this;
    }



    public function toSQL(): string
    {


        $columns = implode(', ', $this->columns);
        $sql = "SELECT $columns FROM $this->table";

        if ($this->_conditions)
            $sql .= ' ' . $this->whereToSQL();

        if ($this->_groupByColumns)
            $sql .= ' ' . $this->groupByToSQL();

        if ($this->_order)
            $sql .= ' ' . $this->orderByToSQL();

        if ($this->_limit !== null)
            $sql .= ' ' . $this->limitToSQL();

        if ($this->_offset)
            $sql .= " OFFSET $this->_offset";

        return $sql;
    }
}
