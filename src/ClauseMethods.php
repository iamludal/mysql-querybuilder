<?php

namespace Ludal\QueryBuilder;

interface ClauseMethods
{
    /**
     * Validate the clause
     * 
     * @throws InvalidQueryException if the clause is invalid/incomplete
     */
    public function validate();

    /**
     * Convert the clause into a SQL query string
     * 
     * @return string the corresponding SQL query
     */
    public function toSQL(): string;
}
