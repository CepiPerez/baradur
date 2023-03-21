<?php

function env($val, $default=null) { 
    return defined($val)? constant($val) : $default;
}

if ( !function_exists('json_decode') )
{
    function json_decode($content, $assoc=false) {
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
    function json_encode($content) {
        include_once(_DIR_.'vendor/json/json.php');
        $json = new Services_JSON;  
        return $json->encode($content);
    }
}

if ( !function_exists('lcfirst') )
{
    function lcfirst($content) {
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
    function str_starts_with($haystack, $needle) {
        return 0 === strncmp($haystack, $needle, strlen($needle));
    }
}

if (!function_exists('str_ends_with'))
{
    function str_ends_with($haystack, $needle) {
        if ('' === $needle || $needle === $haystack) {
            return true;
        }
        if ('' === $haystack) {
            return false;
        }
        $needleLength = strlen($needle);
        return $needleLength <= strlen($haystack) && 0 === substr_compare($haystack, $needle, -$needleLength);
    }
}

if (!function_exists('prettyPrint'))
{
    function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $in_quotes = false;
        $in_escape = false;
        $ends_line_level = NULL;
        $json_length = strlen( $json );

        for( $i = 0; $i < $json_length; $i++ ) {
            $char = $json[$i];
            $new_line_level = NULL;
            $post = "";
            if( $ends_line_level !== NULL ) {
                $new_line_level = $ends_line_level;
                $ends_line_level = NULL;
            }
            if ( $in_escape ) {
                $in_escape = false;
            } else if( $char === '"' ) {
                $in_quotes = !$in_quotes;
            } else if( ! $in_quotes ) {
                switch( $char ) {
                    case '}': case ']':
                        $level--;
                        $ends_line_level = NULL;
                        $new_line_level = $level;
                        break;

                    case '{': case '[':
                        $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ": case "\t": case "\n": case "\r":
                        $char = "";
                        $ends_line_level = $new_line_level;
                        $new_line_level = NULL;
                        break;
                }
            } else if ( $char === '\\' ) {
                $in_escape = true;
            }
            if( $new_line_level !== NULL ) {
                $result .= "\n".str_repeat( "\t", $new_line_level );
            }
            $result .= $char.$post;
        }

        return $result;
    }
}