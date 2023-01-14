<?php

Class Helpers
{
    //private static $_request;

    public static function camelCaseToSnakeCase($name, $plural=true)
    {
        $converted = preg_replace('/([A-Z])/', '_$1', $name);
        $converted = ltrim(strtolower($converted), '_');
        if ($plural)
            return self::getPlural($converted);
        else
            return $converted;
    }

    public static function snakeCaseToCamelCase($name)
    {
        $converted = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        return strtolower(substr($converted, 0, 1)) . substr($converted, 1, strlen($converted)-1);
    }

    public static function camelCaseToKebabCase($name)
    {
        $converted = preg_replace('/([A-Z])/', '-$1', $name);
        $converted = ltrim(strtolower($converted), '-');
        return $converted;
    }

    public static function getPlural($string)
    {
        global $locale, $fallback_locale;

        $filepath = _DIR_.'lang/'.$locale.'/plurals.php';
        
        if (!file_exists($filepath))
            $filepath = _DIR_.'lang/'.$fallback_locale.'/plurals.php';

        if (!file_exists($filepath))
            throw new Exception("FILE $filepath NOT FOUND\n");
        
        $lang = CoreLoader::loadConfigFile($filepath);
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

    public static function getSingular($string)
    {
        global $locale, $fallback_locale, $artisan;

        $filepath = _DIR_.'lang/'.$locale.'/plurals.php';
        
        if (!file_exists($filepath))
            $filepath = _DIR_.'lang/'.$fallback_locale.'/plurals.php';

        if (!file_exists($filepath))
            throw new Exception("FILE $filepath NOT FOUND\n");

        $lang = CoreLoader::loadConfigFile($filepath);
        $result = '';
        //dd($lang);
        foreach ($lang as $key => $value)
        {
            $res = $string;
            $len = strlen($value);
            if (substr($res, -$len) == $value)
            {
                $result = substr($res, 0, strlen($res)-$len) . $key;
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

    public static function trans($string, $placeholder=null)
    {
        global $locale, $fallback_locale;
        $array = explode('.', $string);

        $file = array_shift($array);

        $filepath = _DIR_.'lang/'.$locale.'/'.$file.'.php';
        
        if (!file_exists($filepath))
            $filepath = _DIR_.'lang/'.$fallback_locale.'/'.$file.'.php';

        if (file_exists($filepath))
        {
            $lang = CoreLoader::loadConfigFile($filepath);
        }
        else
        {
            $filepath = _DIR_.'lang/'.$locale.'.json';
            
            if (!file_exists($filepath))
                $filepath = _DIR_.'lang/'.$fallback_locale.'.json';

            if (file_exists($filepath))
            {
                $lang = json_decode(file_get_contents($filepath, 'r'), true);
                return $lang[$string] ? $lang[$string] : $string;
            }
        }

        $value = array_shift($array);
        $result = $lang[$value] ? $lang[$value] : $value;

        while (count($array)>0)
        {
            $value = array_shift($array);
            $result = isset($result[$value]) ? $result[$value] : $value;
        }
            

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

        $helper = null;
        if (is_array($value))
        {
            $helper = array_keys($value);
            $helper = $helper[0];
            $value = $value[$helper];
        }

        if (count($res)==2)
        {
            if ($value==1) return str_replace(':'.$helper, $value, $res[0]);
            else return str_replace(':'.$helper, $value, $res[1]);
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
        return str_replace(':'.$helper, $value, $selected);
    }

    public static function config($val)
    {

        $array = explode('.', $val);
        $file = array_shift($array);

        if (!file_exists(_DIR_.'config/'.$file.'.php'))
            throw new Exception("File not found: $file.php");

        $config = CoreLoader::loadConfigFile(_DIR_.'config/'.$file.'.php');

        $value = array_shift($array);
        $result = $config[$value] ? $config[$value] : $value;

        while (count($array)>0)
        {
            $value = array_shift($array);            
            $result = $result[$value] ? $result[$value] : $value;
        }
        
        return $result;
    }

    public static function verifiedArray($array)
    {
        $new = array();
        $current = 'get';
        if (count(array_keys($array))==0)
        {
            foreach ($array as $val)
            {
                $new[$current] = $val;
                $current = 'set';
            }
        }
        else
        {
            foreach ($array as $key => $val)
            {
                if (!isset($key))
                {
                    $new[$current] = $val;
                    $current = 'set';
                }
                else
                {
                    $new[$key] = $val;
                }
            }
        }
        return $new;
    }

    public static function toCssClasses($array)
    {
        $classList = $array;
        $classes = array();

        foreach ($classList as $class => $constraint) {
            if (is_numeric($class)) {
                $classes[] = $constraint;
            } elseif ($constraint) {
                $classes[] = $class;
            }
        }

        return implode(' ', $classes);
    }


}