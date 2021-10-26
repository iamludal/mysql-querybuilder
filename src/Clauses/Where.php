<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Utils;

trait Where
{
    protected $conditions;

    /**
     * Add a select condition (WHERE clause). The conditions can be as strings
     * or associative array.
     * 
     * @param ...$conditions the condition
     * @throws InvalidArgumentException if any condition is not a string/array
     */
    public function where(...$conditions)
    {
        foreach ($conditions as $condition) {
            if (Utils::isAssociativeArray($condition)) {
                $conds = [];

                foreach ($condition as $key => $value) {
                    $conds[] = "$key = :_$key";
                    $this->setParam($key, $value);
                }

                return $this->where(...$conds);
            }
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
     * @param ...$conditions the conditions
     * @throws InvalidArgumentException if any condition is not a string
     */
    public function orWhere(...$conditions): self
    {
        $this->where(...$conditions);
        return $this;
    }

    /**
     * Convert the current WHERE into a SQL string
     */
    protected function whereToSQL(): string
    {
        $conditions = implode(') OR (', $this->conditions);
        return "WHERE ($conditions)";
    }
}
