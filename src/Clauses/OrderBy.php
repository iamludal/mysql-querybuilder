<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;

trait OrderBy
{
    /**
     * @var string[] the columns to order by
     */
    private $_order = [];

    /**
     * Add ORDER BY clause to the query
     *
     * @param string $column the column to select
     * @param string|null $direction (optional) the direction (ASC or DESC)
     * @return $this
     * @throws InvalidArgumentException if the direction is invalid
     */
    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC']))
            throw new InvalidArgumentException('Direction should be either ASC or DESC');

        $this->_order[] = "$column $direction";
        return $this;
    }

    /**
     * Convert the current ORDER BY to an SQL string
     *
     * @return string the generated SQL
     */
    private function orderByToSQL(): string
    {
        return 'ORDER BY ' . implode(', ', $this->_order);
    }
}
