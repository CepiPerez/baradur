<?php

class Stringable
{
    protected $value;

    public function __construct($value = '')
    {
        $this->value = (string) $value;
    }

    public function __toString(){
        return $this->value;
    }

    public function value()
    {
        return $this->value;
    }

    public function toString()
    {
        return (string)$this->value;
    }

    public function toInteger()
    {
        return intval($this->value);
    }

    public function toFloat()
    {
        return floatval($this->value);
    }

    public function toBoolean()
    {
        if (in_array(strtolower($this->value), array('on', 'yes', 'true', '1', 1), true))
            return true;

        if (in_array(strtolower($this->value), array('off', 'no', 'false', '0', 0), true))
            return false;
            
        return null;
    }

    public function isUrl()
    {
        return Str::isUrl($this->value);
    }

    public function isUuid()
    {
        return Str::isUuid($this->value);
    }

    public function isUlid()
    {
        return Str::isUlid($this->value);
    }

    public function append($values)
    {
        $this->value .= $values;
        return $this;
    }

    public function before($search)
    {
        $this->value = Str::before($this->value, $search);
        return $this;
    }

    public function beforeLast($search)
    {
        $this->value = Str::beforeLast($this->value, $search);
        return $this;
    }
    
    public function between($from, $to)
    {
        $this->value = Str::between($this->value, $from, $to);
        return $this;
    }

    public function betweenFirst($from, $to)
    {
        $this->value = Str::betweenFirst($this->value, $from, $to);
        return $this;
    }

    public function after($search)
    {
        $this->value = Str::after($this->value, $search);
        return $this;
    }

    public function afterLast($search)
    {
        $this->value = Str::afterLast($this->value, $search);
        return $this;
    }

    public function contains($needles)
    {
        return Str::contains($this->value, $needles);
    }

    public function camel()
    {
        $this->value = Str::camel($this->value);
        return $this;
    }

    public function kebab()
    {
        $this->value = Str::kebab($this->value);
        return $this;
    }

    public function length($encoding = null)
    {
        return Str::length($this->value, $encoding);
    }

    public function lower()
    {
        $this->value = Str::lower($this->value);
        return $this;
    }

    public function words($words = 100, $end = '...')
    {
        $this->value = Str::words($this->value, $words, $end);
        return $this;
    }

    public function plural($count = 2)
    {
        $this->value = Str::plural($this->value, $count);
        return $this;
    }

    public function repeat(int $times)
    {
        $this->value = str_repeat($this->value, $times);
        return $this;
    }

    public function replace($search, $replace, $caseSensitive = true)
    {
        $this->value = Str::replace($search, $replace, $this->value, $caseSensitive);
        return $this;
    }

    public function replaceFirst($search, $replace)
    {
        $this->value = Str::replaceFirst($search, $replace, $this->value);
        return $this;
    }

    public function replaceLast($search, $replace)
    {
        $this->value = Str::replaceLast($search, $replace, $this->value);
        return $this;
    }

    public function start($prefix)
    {
        $this->value = Str::start($this->value, $prefix);
        return $this;
    }

    public function reverse()
    {
        $this->value = Str::reverse($this->value);
        return $this;
    }

    public function upper()
    {
        $this->value = Str::upper($this->value);
        return $this;
    }

    public function title()
    {
        $this->value = Str::title($this->value);
        return $this;
    }

    public function singular()
    {
        $this->value = Str::singular($this->value);
        return $this;
    }

    public function slug($separator = '-', $language = 'en')
    {
        $this->value = Str::slug($this->value, $separator, $language);
        return $this;
    }

    public function snake($delimiter = '_')
    {
        $this->value = Str::snake($this->value, $delimiter);
        return $this;
    }

    public function squish()
    {
        $this->value = Str::squish($this->value);
        return $this;
    }

    public function startsWith($needles)
    {
        return Str::startsWith($this->value, $needles);
    }

    public function endsWith($needles)
    {
        return Str::endsWith($this->value, $needles);
    }

    public function substr($start, $length = null)
    {
        $this->value = Str::substr($this->value, $start, $length);
        return $this;
    }

    public function substrCount($needle, $offset = null, $length = null)
    {
        return Str::substrCount($this->value, $needle, $offset ? $offset : 0, $length);
    }

    public function substrReplace($replace, $offset = 0, $length = null)
    {
        $this->value = Str::substrReplace($this->value, $replace, $offset, $length);
        return $this;
    }

    public function swap(array $map)
    {
        $this->value = strtr($this->value, $map);
        return $this;
    }

    public function trim($characters = null)
    {
        $trim = $characters? trim($this->value, $characters) : trim($this->value);
        return new Stringable($trim);
    }

    public function ltrim($characters = null)
    {
        $trim = $characters? ltrim($this->value, $characters) : rtrim($this->value);
        return new Stringable($trim);
    }

    public function rtrim($characters = null)
    {
        $trim = $characters? rtrim($this->value, $characters) : rtrim($this->value);
        return new Stringable($trim);
    }

    public function lcfirst()
    {
        $this->value = Str::lcfirst($this->value);
        return $this;
    }

    public function ucfirst()
    {
        $this->value = Str::ucfirst($this->value);
        return $this;
    }

    public function ucsplit()
    {
        return Str::ucsplit($this->value);
    }

    public function wordCount()
    {
        return str_word_count($this->value);
    }

    public function mask($character, $index, $length = null)
    {
        return Str::mask($this->value, $character, $index, $length);
    }

    public function match($pattern)
    {
        $this->value = Str::match($pattern, $this->value);
        return $this;
    }

    public function isMatch($pattern)
    {
        return Str::isMatch($pattern, $this->value);
    }

    public function matchAll($pattern)
    {
        return Str::matchAll($pattern, $this->value);
    }

}