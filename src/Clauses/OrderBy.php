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
     * @param array $columns the columns to order by (as an associative array: column => direction)
     * @return $this
     * @throws InvalidArgumentException if the direction is invalid
     */
    public function orderBy(array $columns): self
    {
        foreach ($columns as $column => $direction) {
            if (is_int($column)) {
                $column = $direction;
                $direction = null;
            } else {
                $direction = strtoupper($direction);
            }

            if (!is_string($column)) {
                throw new InvalidArgumentException('The column must be a string');
            } elseif (is_string($direction) && !in_array($direction, ['ASC', 'DESC'])) {
                throw new InvalidArgumentException('Direction should be either ASC or DESC');
            }

            $this->_order[] = is_null($direction) ? $column : "$column $direction";
        }

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
