<?php

function env($val, $default=null) { 
    return constant($val)? constant($val) : $default;
}

$base = '/'. rtrim(env('APP_FOLDER'), '/');

$home = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') .
        "://" . $_SERVER['SERVER_NAME'] . $base;


#define('_ASSETS', 'assets');
define('HOME', rtrim($home, '/'));

#define('HOME', env('APP_URL'));

$locale = 'en';

if ( !function_exists('json_decode') )
{
    function json_decode($content, $assoc=false){
        require_once(_DIR_.'/../json/json.php');
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
        require_once(_DIR_.'/../json/json.php');
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

/* if(!function_exists('array_column'))
{

    function array_column($array,$column_name)
    {

        return array_map(function($element) use($column_name){return $element[$column_name];}, $array);

    }

} */

?>