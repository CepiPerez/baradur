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
    
    public static function orderedUuid($data=null)
    {
        return self::getInstance(Uuid::uuid_generate_time());
    }

    public static function uuid($data=null)
    {
        return self::getInstance(Uuid::uuid_generate_random());
    }

    public static function isUuid($uuid)
    {
        return UUid::isValid($uuid);
    }

    public static function ulid($time = null)
    {
        return self::getInstance(Ulid::generate($time));
    }

    public static function isUlid($uuid)
    {
        return Ulid::isValid($uuid);
    }

    public static function before($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $result = strstr($subject, (string) $search, true);

        return $result === false ? $subject : $result;
    }

    public static function beforeLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $pos = strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return self::substr($subject, 0, $pos);
    }

    public static function between($subject, $from, $to)
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return self::beforeLast(self::after($subject, $from), $to);
    }

    public static function betweenFirst($subject, $from, $to)
    {
        if ($from === '' || $to === '') {
            return $subject;
        }

        return self::before(self::after($subject, $from), $to);
    }

    public static function after($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }
        else {
            $res = array_reverse(explode($search, $subject, 2));
            return $res[0];
        }
    }
    
    public static function afterLast($subject, $search)
    {
        if ($search === '') {
            return $subject;
        }

        $position = strrpos($subject, (string) $search);

        if ($position === false) {
            return $subject;
        }

        return substr($subject, $position + strlen($search));
    }
    
    public static function contains($haystack, $needles, $ignoreCase = false)
    {
        if ($ignoreCase) {
            $haystack = strtolower($haystack);
            $needles = array_map('strtolower', (array) $needles);
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
        return strtolower($value);
    }

    public static function length($value = null)
    {
        return strlen($value);
    }

    public static function mapCallback($word) {
        return self::ucfirst($word);
    }

    public static function studly($value)
    {
        $studlyWords = array();

        $words = explode(' ', self::replace(array('-', '_'), ' ', $value));

        foreach ($words as $word) {
            $studlyWords[] = self::mapCallback($word);
        }

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

    public static function plural($value, $count = 2)
    {
        return $count>1? Helpers::getPlural($value) : $value;
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

    public static function replace($search, $replace, $subject, $caseSensitive = true)
    {
        return $caseSensitive
            ? str_replace($search, $replace, $subject)
            : str_ireplace($search, $replace, $subject);
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
        return implode(array_reverse(str_split($value)));
    }

    public static function start($value, $prefix)
    {
        $quoted = preg_quote($prefix, '/');

        return $prefix.preg_replace('/^(?:'.$quoted.')+/u', '', $value);
    }

    public static function upper($value)
    {
        return strtoupper($value);
    }

    public static function title($value)
    {
        //return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        $words = explode(' ', $value);
        $final = array();

        foreach ($words as $word) {
            $final[] = self::ucfirst($word);
        }

        return implode(' ', $final);
    }

    public static function singular($value)
    {
        return Helpers::getSingular($value);
    }

    public static function slug($title, $separator = '-', $language = 'en')
    {
        $flip = $separator === '-' ? '_' : '-';

        $title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

        $title = str_replace('@', $separator.'at'.$separator, $title);

        $title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', self::lower($title));

        $title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

        return trim($title, $separator);
    }

    public static function snake($value, $delimiter = '_')
    {
        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = self::lower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $value));
        }

        return $value;
    }

    public static function squish($value)
    {
        return preg_replace('~(\s|\x{3164})+~u', ' ', preg_replace('~^\s+|\s+$~u', '', $value));
    }

    public static function startsWith($haystack, $needles)
    {
        $needles = is_array($needles)? $needles : array($needles);

        foreach ($needles as $needle) {
            if ((string) $needle !== '' && str_starts_with($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function endsWith($haystack, $needles)
    {
        $needles = is_array($needles)? $needles : array($needles);

        foreach ($needles as $needle) {
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
        return $length? substr($string, $start, $length) : substr($string, $start);
    }

    public static function substrCount($haystack, $needle, $offset = 0, $length = null)
    {
        if (! is_null($length)) {
            return substr_count($haystack, $needle, $offset, $length);
        }

        return substr_count($haystack, $needle, $offset);
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

    public static function is($pattern, $value)
    {
        $patterns = is_array($pattern) ? $pattern : (array) $pattern;

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern == $value) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');

            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function password($length = 32, $letters = true, $numbers = true, $symbols = true, $spaces = false)
    {
        $chars = array();

        if ($letters) {
            $chars = array_merge($chars, array(
                'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
                'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
                'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
                'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
                'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            ));
        }

        if ($numbers) {
            $chars = array_merge($chars, array(
                '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            ));
        }
        if ($symbols) {
            $chars = array_merge($chars, array(
                '~', '!', '#', '$', '%', '^', '&', '*', '(', ')', '-',
                '_', '.', ',', '?', '/', '\\', '{', '}', '[',
                ']', '|', ':', ';',
            ));
        }
        if ($spaces) {
            $chars = array_merge($chars, array(
                ' ',
            ));
        }

        $result = array();

        for ($i=0; $i < $length; $i++) {
            //$c = $chars[random_int(0, count($chars)-1)];
            //dump($c. " : ".$i);
            $result[] = $chars[random_int(0, count($chars)-1)];
        }

        return implode('', $result);
    }

    public static function mask($string, $character, $index, $length = null)
    {
        if ($character === '') {
            return $string;
        }

        if (is_string($index)) {
            $index = strpos($string, $index)!==false ? strpos($string, $index) + 1 : 0;
        } 

        if ($length && is_string($length)) {
            $length = strpos($string, $length)!==false ? strpos($string, $length) - $index : null;
        }

        $segment = $length? substr($string, $index, $length) : substr($string, $index);

        if ($segment === '') {
            return $string;
        }

        $strlen = mb_strlen($string);
        $startIndex = $index;

        if ($index < 0) {
            $startIndex = $index < -$strlen ? 0 : $strlen + $index;
        }

        $start = substr($string, 0, $startIndex);
        $segmentLen = mb_strlen($segment);
        $end = substr($string, $startIndex + $segmentLen);

        return $start.str_repeat(substr($character, 0, 1), $segmentLen).$end;
    }

    public static function match($pattern, $subject)
    {
        preg_match($pattern, $subject, $matches);

        if (! $matches) {
            return '';
        }

        return $matches[1] ? $matches[1] : $matches[0];
    }

    public static function isMatch($pattern, $value)
    {
        $value = (string) $value;

        if (! is_array($pattern)) {
            $pattern = array($pattern);
        }

        foreach ($pattern as $pattern) {
            $pattern = (string) $pattern;

            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }

        return false;
    }

    public static function matchAll($pattern, $subject)
    {
        preg_match_all($pattern, $subject, $matches);

        if (empty($matches[0])) {
            return collect();
        }

        return collect($matches[1] ? $matches[1] : $matches[0]);
    }

}