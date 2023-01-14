<?php

function env($val, $default=null) { 
    return defined($val)? constant($val) : $default;
}

$base = '/'. rtrim(env('PUBLIC_FOLDER'), '/');

/* $home = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') .
        "://" . $_SERVER['SERVER_NAME'] . $base; */

$home = rtrim(env('APP_URL'), '/') . $base;

#define('_ASSETS', 'assets');
define('HOME', rtrim($home, '/'));

#define('HOME', env('APP_URL'));

$locale = 'en';

if ( !function_exists('json_decode') )
{
    function json_decode($content, $assoc=false){
        include(_DIR_.'vendor/json/json.php');
        if ( $assoc ){
            $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        } else {
            $json = new Services_JSON;
        }
        return $json->decode($content);
    }
}
else
{
    function json_decode2($content, $assoc=false){
        include(_DIR_.'vendor/json/json.php');
        if ( $assoc ){
            $json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
        } else {
            $json = new Services_JSON;
        }
        return $json->decode($content);
    }
}

if ( !function_exists('json_encode') )
{
    function json_encode($content){
        //var_dump($content);
        include_once(_DIR_.'vendor/json/json.php');
        $json = new Services_JSON;  
        return $json->encode($content);
    }
}
else
{
    function json_encode2($content){
        //var_dump($content);
        include_once(_DIR_.'vendor/json/json.php');
        $json = new Services_JSON;  
        return $json->encode($content);
    }

}
if ( !function_exists('lcfirst') )
{
    function lcfirst($content){
        $first = strtolower(substr($content, 0, 1));
        $rest = (strlen($content) > 1)? substr($content, 1, strlen($content)-1) : '';
        return $first.$rest;
    }
}

if (!function_exists('str_contains'))
{
    function str_contains($haystack, $needle)
    {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

if (!function_exists('str_starts_with'))
{
    function str_starts_with($haystack, $needle)
    {
        return 0 === strncmp($haystack, $needle, \strlen($needle));
    }
}

if (!function_exists('str_ends_with'))
{
    function str_ends_with($haystack, $needle)
    {
        if ('' === $needle || $needle === $haystack) {
            return true;
        }

        if ('' === $haystack) {
            return false;
        }

        $needleLength = \strlen($needle);

        return $needleLength <= \strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needleLength);
    }

}

/* if(!function_exists('array_column'))
{

    function array_column($array,$column_name)
    {

        return array_map(function($element) use($column_name){return $element[$column_name];}, $array);

    }

} */

?>