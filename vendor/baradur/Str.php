<?php

class Str
{
    protected static $_instance = null;
    //public static $_macros = array();

    private static function getInstance($string = null)
    {
        return new Stringable($string);
    }


    /* public static function marcro($name, $callback)
    {
        self::$_macros[$name] = $callback;
    } */


    public static function of($string)
    {
        return self::getInstance($string);
    }


    /* function __toString(){
        return $this->value;
    } */


    public static function uuid($data=null)
    {
        if (!$data)
            $data = random_bytes(16);

        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function after($subject, $search)
    {
        if ($search === '') 
            return $subject;
        else {
            $res = array_reverse(explode($search, $subject, 2));
            return $res[0];
        }
    }
    
    public static function contains($haystack, $needles, $ignoreCase = false)
    {
        if ($ignoreCase) {
            $haystack = mb_strtolower($haystack);
            $needles = array_map('mb_strtolower', (array) $needles);
        }

        foreach ((array) $needles as $needle) {
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function camel($value)
    {
        return lcfirst(self::studly($value));
    }

    public static function kebab($value)
    {
        return self::snake($value, '-');
    }

    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }

    public static function length($value, $encoding = null)
    {
        if ($encoding) {
            return mb_strlen($value, $encoding);
        }

        return mb_strlen($value);
    }


    public static function mapCallback($word) {
        return self::ucfirst($word);
    }

    public static function studly($value)
    {
        $key = $value;

        $words = explode(' ', self::replace(array('-', '_'), ' ', $value));

        foreach ($words as $word)
            $studlyWords[] = self::mapCallback($word);

        return implode($studlyWords);
    }

    public static function words($value, $words = 100, $end = '...')
    {
        preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

        if (! isset($matches[0]) || self::length($value) === self::length($matches[0])) {
            return $value;
        }

        return rtrim($matches[0]).$end;
    }

    public static function plural($value) //, $count = 2)
    {
        return Helpers::getPlural($value); //, $count);
    }

    public static function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(array('/', '+', '='), '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    public static function repeat(string $string, int $times)
    {
        return str_repeat($string, $times);
    }

    public static function replace($search, $replace, $subject)
    {
        return str_replace($search, $replace, $subject);
    }

    public static function replaceFirst($search, $replace, $subject)
    {
        $search = (string) $search;

        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    public static function replaceLast($search, $replace, $subject)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    public static function reverse(string $value)
    {
        return implode(array_reverse(mb_str_split($value)));
    }

    public static function start($value, $prefix)
    {
        $quoted = preg_quote($prefix, '/');

        return $prefix.preg_replace('/^(?:'.$quoted.')+/u', '', $value);
    }

    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public static function singular($value)
    {
        return Helpers::getSingular($value);
    }

    public static function slug($title, $separator = '-', $language = 'en')
    {
        //$title = $language ? self::ascii($title, $language) : $title;

        // Convert all dashes/underscores into separator
        $flip = $separator === '-' ? '_' : '-';

        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

        // Replace @ with the word 'at'
        $title = str_replace('@', $separator.'at'.$separator, $title);

        // Remove all characters that are not the separator, letters, numbers, or whitespace.
        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', self::lower($title));

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

        return trim($title, $separator);
    }

    public static function snake($value, $delimiter = '_')
    {
        //$key = $value;

        /* if (isset(static::$snakeCache[$key][$delimiter])) {
            return static::$snakeCache[$key][$delimiter];
        } */

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = self::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return $value; //static::$snakeCache[$key][$delimiter] = $value;
    }

    public static function squish($value)
    {
        return preg_replace('~(\s|\x{3164})+~u', ' ', preg_replace('~^\s+|\s+$~u', '', $value));
    }

    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if (
                $needle !== '' && $needle !== null
                && str_ends_with($haystack, $needle)
            ) {
                return true;
            }
        }

        return false;
    }

    public static function substr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length, 'UTF-8');
    }

    public static function substrCount($haystack, $needle, $offset = 0, $length = null)
    {
        if (! is_null($length)) {
            return substr_count($haystack, $needle, $offset, $length);
        } else {
            return substr_count($haystack, $needle, $offset);
        }
    }

    public static function substrReplace($string, $replace, $offset = 0, $length = null)
    {
        if ($length === null) {
            $length = strlen($string);
        }

        return substr_replace($string, $replace, $offset, $length);
    }

    public static function swap(array $map, $subject)
    {
        return strtr($subject, $map);
    }

    public static function lcfirst($string)
    {
        return self::lower(self::substr($string, 0, 1)).self::substr($string, 1);
    }

    public static function ucfirst($string)
    {
        return self::upper(self::substr($string, 0, 1)).self::substr($string, 1);
    }

    public static function ucsplit($string)
    {
        return preg_split('/(?=\p{Lu})/u', $string, -1, PREG_SPLIT_NO_EMPTY);
    }

    public static function wordCount($string, $characters = null)
    {
        return str_word_count($string, 0, $characters);
    }

    

}