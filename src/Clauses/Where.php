<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Exceptions\UnknownType;
use Ludal\QueryBuilder\Utils;

trait Where
{
    protected $conditions;

    /**
     * Add a select condition (WHERE clause). The conditions can be as strings
     * or associative array.
     *
     * @param mixed ...$conditions the condition
     * @return $this
     * @throws InvalidArgumentException if any condition is not a string/array
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if a param value has an unknown type
     */
    public function where(...$conditions): self
    {
        foreach ($conditions as $condition) {
            if (Utils::isAssociativeArray($condition)) {
                $string_conditions = [];

                foreach ($condition as $key => $value) {
                    $string_conditions[] = "$key = :_$key";
                    $this->setParam($key, $value);
                }

                return $this->where(...$string_conditions);
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
     * @param mixed ...$conditions the conditions
     * @return $this
     * @throws InvalidArgumentException if any condition is not a string
     * @throws InvalidQueryException if the query is invalid
     * @throws UnknownType if a param value has an unknown type
     */
    public function orWhere(...$conditions): self
    {
        $this->where(...$conditions);
        return $this;
    }

    /**
     * Convert the current WHERE into an SQL string
     *
     * @return string the generated SQL
     */
    protected function whereToSQL(): string
    {
        $conditions = implode(') OR (', $this->conditions);
        return "WHERE ($conditions)";
    }
}
