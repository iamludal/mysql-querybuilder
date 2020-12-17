<?php

namespace Ludal\QueryBuilder\Clauses;

use Ludal\QueryBuilder\Exceptions\InvalidQueryException;

class Update extends WhereClause
{
    /**
     * @var array the params, in the form : "id = 4", "age = 20"...
     */
    private $updateParams = [];

    /**
     * Set the values to update
     * 
     * @param ...$values either a string, that is directly the value to set 
     * ("id = 5", ...) or an associative array of the form: [$col => $val, ...]
     */
    public function set(...$values): self
    {
        foreach ($values as $value)
            if (is_string($value))
                $this->updateParams[] = $value;
            elseif (is_array($value))
                foreach ($value as $key => $val)
                    $this->setValue($key, $val);

        return $this;
    }

    /**
     * Set a value for the column to be updated
     * 
     * @param $column the column name
     * @param $value the value to set
     */
    public function setValue(string $column, $value): self
    {
        $this->updateParams[] = "$column = :_$column";
        $this->params[$column] = $value;

        return $this;
    }

    public function validate(): void
    {
        $conditions = [
            is_string($this->table),
            count($this->params + $this->updateParams) > 0,
        ];

        if (in_array(false, $conditions))
            throw new InvalidQueryException();
    }

    public function toSQL(): string
    {
        $this->validate();

        $sql = "UPDATE {$this->table} SET ";

        $sql .= implode(', ', $this->updateParams);

        if ($this->conditions)
            $sql .= ' ' . $this->whereToSQL();

        return $sql;
    }
}
