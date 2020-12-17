<?php

namespace Ludal\QueryBuilder\Clauses;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Delete extends WhereClause
{

    public function validate(): void
    {
        $conditions = [
            is_string($this->table)
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException();
    }

    public function toSQL(): string
    {
        $this->validate();

        $sql = "DELETE FROM {$this->table}";

        if ($this->conditions)
            $sql .= ' ' . $this->whereToSQL();

        return $sql;
    }
}
