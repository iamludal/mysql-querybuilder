<?php

namespace Ludal\QueryBuilder;

use InvalidArgumentException;
use SebastianBergmann\Type\UnknownType;
use PDO;

class Utils
{
    /**
     * Get the corresponding PDO type for a given value
     * 
     * @param mixed $value the value
     * @return int the PDO type
     * @throws UnknownType if the PDO type couldn't be determined
     * @throws InvalidArgumentException if the type is incorrect
     */
    public static function getPDOType($value)
    {
        switch (gettype($value)) {
            case 'string':
            case 'double':
                return PDO::PARAM_STR;
            case 'boolean':
                return PDO::PARAM_BOOL;
            case 'integer':
                return PDO::PARAM_INT;
            case 'NULL':
                return PDO::PARAM_NULL;
            case 'resource':
                return PDO::PARAM_LOB;
            case 'array':
            case 'object':
                throw new InvalidArgumentException('Incorrect type');
            default:
                throw new UnknownType('Unknown type, please set it explicitly');
        }
    }

    /**
     * Verify if the given value is an associative array
     * 
     * @param mixed $subject the subject
     * @return bool true if the given value is an associative array, false otherwise
     */
    public static function isAssociativeArray($subject): bool
    {
        if (!is_array($subject))
            return false;

        foreach (array_keys($subject) as $key)
            if (!is_string($key))
                return false;

        return true;
    }
}
