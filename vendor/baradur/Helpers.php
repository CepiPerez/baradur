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

    public static function trans($string, $placeholder=null)
    {
        global $locale;
        list($file, $value) = explode('.', $string);

        $filepath = _DIR_.'/../../resources/lang/'.$locale.'/'.$file.'.php';
        if (file_exists($filepath))
        {
            $lang = include $filepath;
        }
        else
        {
            $filepath = _DIR_.'/../../resources/lang/'.$locale.'.json';
            if (file_exists($filepath))
            {
                $lang = json_decode(file_get_contents($filepath, 'r'), true);
                return $lang[$string] ? $lang[$string] : $string;
            }
            else
            {
                $filepath = _DIR_.'/../../resources/lang/en/'.$file.'.php';
                $lang = include $filepath;
            }
        }

        $result = $lang[$value] ? $lang[$value] : $value;

        if ($placeholder)
        {
            foreach ($placeholder as $key => $val)
                $result = str_replace(':'.$key, $val, $result);
        }

        return $result;
        
    }

    public static function trans_choice($string, $value, $placeholder=null)
    {
        $str = self::trans($string, $placeholder);
        $res = explode('|', $str);

        if (count($res)==2)
        {
            if ($value==1) return $res[0];
            else return $res[1];
        }
        else if (count($res)>2)
        {
            $cons = array();
            foreach($res as $r) {
                preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', $r, $matches);
                $cons[] = $matches[1];
            }

            $segments = preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $res);

            $selected = 0;
            $count = 0;
            foreach ($cons as $range)
            {
                $r = explode(',', $range);
                
                if ($r[1]=='*')
                {
                    if ($value >= $r[0])
                    {
                        $selected = $segments[$count];
                        break;
                    }
                }
                else if ($r==$value)
                {
                    $selected = $segments[$count];
                    break;
                }
                else if (in_array($value, range($r[0], $r[1])))
                {
                    $selected = $segments[$count];
                    break;
                }
                ++$count;
            }
        }
        return $selected;
    }


}