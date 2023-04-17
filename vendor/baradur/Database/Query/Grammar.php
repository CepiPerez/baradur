<?php

class Grammar
{
    public $tablePrefix;
    
    public function wrapTable($table)
    {
        if (! $this->isExpression($table)) {
            return $this->wrap($this->tablePrefix.$table, true);
        }

        return $this->getValue($table);
    }

    public function wrap($value, $prefixAlias = false)
    {
        if ($this->isExpression($value)) {
            return $this->getValue($value);
        }

        if (stripos($value, ' as ') !== false) {
            return $this->wrapAliasedValue($value, $prefixAlias);
        }

        if (strpos($value, ' ')!==false) {
            return $value;
        }

        /* if ($this->isJsonSelector($value)) {
            return $this->wrapJsonSelector($value);
        } */

        return $this->wrapSegments(explode('.', $value));
    }

    protected function wrapAliasedValue($value, $prefixAlias = false)
    {
        $segments = preg_split('/\s+as\s+/i', $value);

        // Alias prefix not implemented yet
        /* if ($prefixAlias) {
            $segments[1] = $this->tablePrefix.$segments[1];
        } */

        return $this->wrap($segments[0]).' as '.$this->wrapValue($segments[1]);
    }

    protected function wrapSegments($segments)
    {
        while (count($segments) > 2) {
            array_shift($segments);
        }

        $result = array();

        foreach ($segments as $segment) {
            $result[] = $this->wrapValue($segment);
        }

        return implode('.', $result);
    }

    protected function wrapValue($value)
    {
        if ($value !== '*') {
            return '`'.$value.'`';
        }

        return $value;
    }

    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    public function getValue($expression)
    {
        return $expression->getValue();
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix? $prefix . '.' : null;

        return $this;
    }

}