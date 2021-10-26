<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use Ludal\QueryBuilder\Utils;

trait GroupBy
{
    protected $groupByColumns;

    /**
     * Add a select groupByColumn (GROUP BY clause). The groupByColumns can be as strings
     * or associative array.
     * 
     * @param ...$groupByColumns the groupByColumn
     * @throws InvalidArgumentException if any groupByColumn is not a string/array
     */
    public function groupBy(...$groupByColumns)
    {
        foreach ($groupByColumns as $groupByColumn) {
            if (Utils::isAssociativeArray($groupByColumn)) {
                $conds = [];

                foreach ($groupByColumn as $key => $value) {
                    $conds[] = "$key $value";
                    $this->setParam($key, $value);
                }

                return $this->groupBy(...$conds);
            }
            if (!is_string($groupByColumn))
                throw new InvalidArgumentException('groupByColumns must be strings');
        }

        if ($groupByColumns)
            $this->groupByColumns[] = implode(', ', $groupByColumns);

        return $this;
    }

    /**
     * Convert the current GROUP BY into a SQL string
     */
    protected function groupByToSQL(): string
    {
        $groupByColumns = implode(', ', $this->groupByColumns);
        return "GROUP BY $groupByColumns";
    }
}
