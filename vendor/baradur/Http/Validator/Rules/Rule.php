<?php

class Rule
{
    
    public static function in($values)
    {
        if ($values instanceof Collection) {
            $values = $values->toArray();
        }

        return new RuleItem('in', is_array($values) ? $values : func_get_args());
    }

    public static function notIn($values)
    {
        if ($values instanceof Collection) {
            $values = $values->toArray();
        }

        return new RuleItem('not_in', is_array($values) ? $values : func_get_args());
    }

    public static function prohibitedIf($value)
    {

        return new RuleItem('prohibited_if', $value);
    }

    public static function requiredIf($value)
    {

        return new RuleItem('required_if', $value);
    }

    public static function unique($value, $column=null)
    {
        return new RuleItem('unique', $value, $column);
    }

}