<?php

# Load variables from .env file

class DotEnv
{

    public static function load($path, $file, $cache=true)
    {
        $lines = file($path.$file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line)
        {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);

            $name = trim($name);

            if (substr($value, 0, 1)==="'") {
                $value = ltrim($value, "'");
                $value = substr($value, 0, strpos($value, "'"));
            } elseif (substr($value, 0, 1)==='"') {
                $value = ltrim($value, '"');
                $value = substr($value, 0, strpos($value, '"'));
            } else {
                $value = explode(' ', $value);
                $value = $value[0];
            }

            $value = trim($value);

            if ($value==="true") $value = true;
            if ($value==="false") $value = false;
            if ($value==="null") $value = null;

            define($name, $value);
        }
    }
}
