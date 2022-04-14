<?php

Class Helpers
{
    private static $_request;

    public static function getTableNameFromClass($class, $plural=true)
    {
        $converted = preg_replace('/([A-Z])/', '_$1', $class);
        $converted = ltrim(strtolower($converted), '_');
        if ($plural)
            return self::getPlural($converted);
        else
            return $converted;
    }

    public static function getPlural($string, $fromCli=false)
    {
        global $locale;

        $filepath = _DIR_.'/../../resources/lang/'.$locale.'/plurals.php';
        
        if (!file_exists($filepath))
            $filepath = _DIR_.'/resources/lang/en/plurals.php';

        
        $lang = include $filepath;
        $result = '';
        foreach ($lang as $key => $value)
        {
            $res = $string;
            $len = strlen($key);
            if (substr($res, -$len) == $key)
            {
                $result = substr($res, 0, strlen($res)-$len) . $value;
                break;
            }
        }
        if ($result == '')
            $result = $string . $lang['*'];

        return $result;

    }

    public static function arrayToObject($array)
    {
        $obj = new stdClass;

        if (count($array)==0)
            return $obj;

        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $obj->$key = self::arrayToObject($value);
            } 
            else
            {
                $obj->$key = $value; 
            }
        }
        return $obj;
    }

    public static function setRequest($req)
    {
        self::$_request = $req;
    }

    public static function getRequest()
    {
        return self::$_request;
    }


}