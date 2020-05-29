<?php

namespace Ludal\QueryBuilder\Clauses;

interface ClauseMethods
{
    /**
     * Convert the clause into a SQL query string
     * 
     * @return string the corresponding SQL query
     */
    public function toSQL(): string;
}
