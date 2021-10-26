<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;
use Ludal\QueryBuilder\Exceptions\UnknownType;
use Ludal\QueryBuilder\Utils;

trait GroupBy
{
    /**
     * @var string[] the columns to group by
     */
    protected $_groupByColumns = [];

    /**
     * Add a select groupByColumn (GROUP BY clause). The groupByColumns can be as strings
     * or associative array.
     *
     * @param mixed ...$groupByColumns the groupByColumn
     * @return $this
     * @throws InvalidArgumentException if any groupByColumn is not a string/array
     * @throws InvalidQueryException if the query is invalid or incomplete
     * @throws UnknownType if a param value has an unknown type
     */
    public function groupBy(...$groupByColumns): self
    {
        foreach ($groupByColumns as $groupByColumn) {
            if (Utils::isAssociativeArray($groupByColumn)) {
                $conditions = [];

                foreach ($groupByColumn as $key => $value) {
                    $conditions[] = "$key $value";
                    $this->setParam($key, $value);
                }

                return $this->groupBy(...$conditions);
            }
            if (!is_string($groupByColumn))
                throw new InvalidArgumentException('groupByColumns must be strings');
        }

        if ($groupByColumns)
            $this->_groupByColumns[] = implode(', ', $groupByColumns);

        return $this;
    }

    /**
     * Convert the current GROUP BY into an SQL string
     *
     * @return string the generated SQL
     */
    protected function groupByToSQL(): string
    {
        $groupByColumns = implode(', ', $this->_groupByColumns);
        return "GROUP BY $groupByColumns";
    }
}
