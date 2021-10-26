<?php

namespace Ludal\QueryBuilder\Clauses;

trait Limit
{
    /**
     * @var int the limit
     */
    private $_limit = null;

    /**
     * Add the LIMIT of rows to select
     *
     * @param int $limit the LIMIT
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->_limit = $limit;
        return $this;
    }

    /**
     * Convert the current LIMIT to an SQL string
     *
     * @return string the generated SQL
     */
    private function limitToSQL(): string
    {
        return "LIMIT $this->_limit";
    }
}