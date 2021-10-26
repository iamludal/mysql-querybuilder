<?php

namespace Ludal\QueryBuilder\Statements;

use Ludal\QueryBuilder\Clauses\Limit;
use Ludal\QueryBuilder\Clauses\OrderBy;
use Ludal\QueryBuilder\Clauses\Where;
use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Delete extends Statement
{
    use Where, OrderBy, Limit;

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

        $sql = "DELETE FROM $this->table";

        if ($this->_conditions)
            $sql .= ' ' . $this->whereToSQL();

        if ($this->_order)
            $sql .= ' ' . $this->orderByToSQL();

        if ($this->_limit !== null)
            $sql .= ' ' . $this->limitToSQL();

        return $sql;
    }
}
