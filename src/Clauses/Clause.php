<?php

namespace Ludal\QueryBuilder\Clauses;

use InvalidArgumentException;
use PDO;

abstract class Clause
{
    protected $pdo;

    /**
     * Create a new clause
     * 
     * @param PDO $pdo (optional) a PDO instance to fetch/execute the clause
     */
    public function __construct($pdo = null)
    {
        if (!is_null($pdo) && !($pdo instanceof PDO))
            throw new InvalidArgumentException('Constructor parameter should be a PDO instance');

        $this->pdo = $pdo;
    }
}
