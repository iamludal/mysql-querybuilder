<?php

namespace Ludal\QueryBuilder\Clauses;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use InvalidArgumentException;

class Insert extends Clause
{
    /**
     * Specify the row to insert in the table. It should be of the form:
     * <code> [$column1 => $value1, $column2 => $value2, ...] </code>
     * 
     * @param $row the values of the row to insert
     * @throws InvalidArgumentException the $row is not an associative array
     */
    public function values(array $row): self
    {
        if (array_values($row) == $row)
            throw new InvalidArgumentException('Value should be an associative array');

        foreach ($row as $key => $value)
            $this->params[$key] = $value;

        return $this;
    }

    public function validate(): void
    {
        $conditions = [
            is_string($this->table),
            count($this->params) > 0,
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException();
    }

    public function toSQL(): string
    {
        $this->validate();

        $table = $this->table;
        $keys = array_keys($this->params);
        $columns = implode(', ', $keys);
        $params = implode(', ', array_map(function ($k) {
            return ":_$k";
        }, $keys));

        $sql = "INSERT INTO $table ($columns) VALUES ($params)";

        return $sql;
    }
}
