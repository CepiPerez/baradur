<?php

function env($val, $default=null) { 
    return defined($val)? constant($val) : $default;
}

if ( !function_exists( 'hex2bin' ) ) {
    function hex2bin( $str ) {
        $sbin = "";
        $len = strlen( $str );
        for ( $i = 0; $i < $len; $i += 2 ) {
            $sbin .= pack( "H*", substr( $str, $i, 2 ) );
        }

        return $sbin;
    }
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

if (!function_exists('getallheaders')) 
{
    function getallheaders() {
        $headers = array();

        foreach ($_SERVER as $name => $value)  {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

       return $headers;
    }
}

/**
 * Return called class name
 *
 * @author Michael Grenier
 * @param int $i_level optional
 * @return string
 */
if (!function_exists('get_called_class')) 
{
    function get_called_class($i_level = 1)
    {
        $a_debug = debug_backtrace();
        $a_called = array();
        $a_called_function = $a_debug[$i_level]['function'];
    
        for ($i = 1, $n = sizeof($a_debug); $i < $n; $i++)
        {
            if (in_array($a_debug[$i]['function'], array('eval')) || 
                strpos($a_debug[$i]['function'], 'eval()') !== false)
                continue;
            
            if (in_array($a_debug[$i]['function'], array('__call', '__callStatic')))
                $a_called_function = $a_debug[$i]['args'][0];
            
            if ($a_debug[$i]['function'] == $a_called_function)
                $a_called = $a_debug[$i];
        }
    
        if (isset($a_called['object']) || isset($a_called['class']))
            return (string)$a_called['class'];
    
        $i_line = (int)$a_called['line'] - 1;
        $a_lines = explode("\n", file_get_contents($a_called['file']));
    
        preg_match("#([a-zA-Z0-9_]+){$a_called['type']}
                    {$a_called['function']}( )*\(#", $a_lines[$i_line], $a_match);
    
        unset($a_debug, $a_called, $a_called_function, $i_line, $a_lines);
    
        if (sizeof($a_match) > 0)
            $s_class = (string)trim($a_match[1]);
        else
            $s_class = (string)$a_called['class'];
    
        if ($s_class == 'self')
            return get_called_class($i_level + 2);
    
        return $s_class;
    }
}

