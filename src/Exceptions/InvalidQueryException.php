<?php

namespace Ludal\QueryBuilder\Exceptions;

use Exception;

class InvalidQueryException extends Exception
{
    protected $message = "Query is invalid or incomplete.";
}
