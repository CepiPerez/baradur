<?php

# Load variables from .env file

class DotEnv
{

    public static function load($path, $file, $cache=true)
    {
        if ($cache)
            $envfile = "<?php\n\n";

        $lines = file($path.$file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line)
        {

            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            //if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV))
            //{
                define($name, $value);

                if ($cache)
                {
                    if (is_numeric($value))
                        $envfile .= "define('$name', $value);\n";
                    else
                        $envfile .= "define('$name', '$value');\n";
                }    
            //}
        }
        if ($cache)
        {
            $envfile .= "\n?>";

            Cache::store('file')->plainPut($path.'/storage/framework/config/env.php', $envfile);

            unset($envfile);
        }
    }
}
