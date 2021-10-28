<?php

namespace Ludal\QueryBuilder\Tests\Mocks;

use Ludal\QueryBuilder\Clauses\OrderBy;

class OrderByMock
{
    use OrderBy;

    public function toSql(): string
    {
        return $this->orderByToSQL();
    }
}