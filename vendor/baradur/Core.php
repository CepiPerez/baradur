<?php

//session_destroy();

# This might be only necessary for local development using Docker
date_default_timezone_set('America/Argentina/Buenos_Aires');

# Global variables
$routes = array();
$observers = array();
$version = '';

ini_set('display_errors', true);
error_reporting(E_ALL + E_NOTICE);
#ini_set('display_errors', false);

/* if (version_compare(phpversion(), '8.0.0', '>='))
{
    ini_set('display_errors', false);
    error_reporting(0);
} */

define ('_DIR_', dirname(__FILE__));

# Autoload function registration
spl_autoload_register('custom_autoloader');

# Environment variables
if (file_exists(_DIR_.'/../../storage/framework/config/env.php'))
{
    require_once(_DIR_.'/../../storage/framework/config/env.php');
}
else
{
    require_once('DotEnv.php');
    DotEnv::load(_DIR_.'/../../', '.env');
}


# Globals
require_once('Globals.php');


# Global functions / Router functions
require_once('Global_functions.php');
require_once('Routing/Route_functions.php');


# Generating Application KEY (for Tokens usage)
require_once(_DIR_.'/../random_compat/lib/random.php');
if (!isset($_SESSION['key']))
    $_SESSION['key'] = bin2hex(random_bytes(32));


# Instantiating App
$app = new App();

# Including config file
$config = CoreLoader::loadConfigFile(_DIR_.'/../../config/app.php');

# Initializing locale
$locale = $config['locale'];
$fallback_locale = $config['fallback_locale'];

# Initializing App cache
$cache = new FileStore(new Filesystem(), _DIR_.'/../../storage/framework/cache/classes', 0777);

# Initializing Storage
Storage::$path = _DIR_.'/../../storage/app/public/';

# Loading Providers
foreach($config['providers'] as $provider)
{
    CoreLoader::loadProvider(_DIR_.'/../../app/providers/'.$provider.'.php');
}

#dd(Route::routeList()); exit();


# Autoload function
function custom_autoloader($class) 
{
    global $version, $home;

    //echo "Loading class: ".$class."<br>";
    $version = version_compare(phpversion(), '5.3.0', '>=')?'NEW':'OLD';

    $newclass = '';
    if (file_exists(_DIR_.'/../../app/models/'.$class.'.php'))
        $newclass = _DIR_.'/../../app/models/'.$class.'.php';
    elseif (file_exists(_DIR_.'/../../app/models/auth/'.$class.'.php'))
        $newclass = _DIR_.'/../../app/models/auth/'.$class.'.php';
    elseif (file_exists(_DIR_.'/../../app/controllers/'.$class.'.php'))
        $newclass = _DIR_.'/../../app/controllers/'.$class.'.php';
    elseif (file_exists(_DIR_.'/../../app/controllers/auth/'.$class.'.php'))
        $newclass = _DIR_.'/../../app/controllers/auth/'.$class.'.php';
    elseif (file_exists(_DIR_.'/../../app/mddleware/'.$class.'.php'))
        $newclass = _DIR_.'/../../app/middleware/'.$class.'.php';
    elseif (file_exists(_DIR_.'/../../app/policies/'.$class.'.php'))
        $newclass = _DIR_.'/../../app/policies/'.$class.'.php';
    elseif (file_exists(_DIR_.'/View/'.$class.'.php'))
        $newclass = _DIR_.'/View/'.$class.'.php';
    elseif (file_exists(_DIR_.'/Database/'.$class.'.php'))
        $newclass = _DIR_.'/Database/'.$class.'.php';
    elseif (file_exists(_DIR_.'/'.$class.'.php'))
        $newclass = _DIR_.'/'.$class.'.php';

    # Recursive search (class is not in predefined folders)
    if ($newclass=='') {
        $it = new RecursiveDirectoryIterator(_DIR_.'/../../app');
        foreach(new RecursiveIteratorIterator($it) as $file)
        {
            if (basename($file) == $class.'.php' || basename($file) == $class.'.PHP')
            {
                $newclass = $file;
                break;
            }
        }
    }

    # Recursive search in database folder
    if ($newclass=='') {
        $it = new RecursiveDirectoryIterator(_DIR_.'/../../database');
        foreach(new RecursiveIteratorIterator($it) as $file)
        {
            if (basename($file) == $class.'.php' || basename($file) == $class.'.PHP')
            {
                $newclass = $file;
                break;
            }
        }
    }

    # Recursive search in vendor folder
    if ($newclass=='') {
        $it = new RecursiveDirectoryIterator(_DIR_.'/../');
        foreach(new RecursiveIteratorIterator($it) as $file)
        {
            if (basename($file) == $class.'.php' || basename($file) == $class.'.PHP')
            {
                $newclass = $file;
                break;
            }
        }
    }


    if (file_exists(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php'))
    {
        $date = filemtime($newclass);
        $cachedate = filemtime(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php');
        if ($date < $cachedate)
        {
            require_once(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php');
            $newclass = '';
        } 
        else
        {
            @unlink(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php');
        }
    }

    if (file_exists(_DIR_.'/../../storage/framework/cache/classes/'.$class.'Model.php'))
    {
        $date = filemtime($newclass);
        $cachedate = filemtime(_DIR_.'/../../storage/framework/cache/classes/'.$class.'Model.php');
        if ($date < $cachedate)
        {
            require_once(_DIR_.'/../../storage/framework/cache/classes/'.$class.'Model.php');
            $newclass = '';
        } 
        else
        {
            @unlink(_DIR_.'/../../storage/framework/cache/classes/'.$class.'Model.php');
        }
    }

    
    if ($newclass!='') // && $version=='OLD')
    {

        $temp = file_get_contents($newclass);
        $temp = str_replace('  ', ' ', $temp);
        if (strpos($temp, ' extends Model')>0)
        {
            //echo "Class ".$class.' is Model's subclass!<br>';

            $temp2 = file_get_contents(_DIR_.'/Database/Model.php');
            $temp2 = str_replace('Model', $class.'Model', $temp2);
            $temp2 = str_replace('myparent', $class, $temp2);
            $temp2 = rtrim($temp2, '}');

            $temp = str_replace('extends Model', 'extends '.$class.'Model', $temp);


            # Get table and query DB to get columns
            # This way we can create where{COLUMN_NAME} methods
            if ($class!='DB')
            {    
                preg_match('/(protected[\s]*\$table[\s]*=)[\s]*(.*);/', $temp, $table);
                if (count($table)!=3)
                    $table = strtolower(Helpers::camelCaseToSnakeCase($class));
                else
                    $table = str_replace("'", "", $table[2]);
    
                $query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS  
                        WHERE TABLE_SCHEMA='".env('DB_NAME')."' AND TABLE_NAME = '$table'";
                $res = DB::table($table)->query($query);
                
                $arr = array();
                if ($res->num_rows>0)
                {
                    while ($row = $res->fetch_assoc()){
                        $arr[] = $row['COLUMN_NAME'];
                    }
                }
    
                if (count($arr)>0)
                {
                    //$temp2 = rtrim($temp2, '}');
                    foreach ($arr as $column)
                    {
                        $colname = Helpers::snakeCaseToCamelCase($column);
                        $temp2 .= "\n   public static function where".ucfirst($colname). '($val) { return self::getInstance()->getQuery()->where(\''.$column.'\', $val); }'."\n";
                    }
                    
                }
            }
            

            # Make custom scopes
            $pattern = "/scope(.*)\(/i";
            if (preg_match_all($pattern, $temp, $matches))
            {
                //$temp2 = rtrim($temp2, '}');
                foreach ($matches[1] as $scope)
                {
                    $temp2 .= "\n   public static function ". lcfirst($scope) ."()
    {
        return self::getInstance()->getQuery()->callScope('". lcfirst($scope) ."', func_get_args());
    }";
                }
            }
            $temp2 .= "\n}\n";


            /* if (file_exists(_DIR_.'/../../storage/framework/'.$class.'Model.php'))
                unlink(_DIR_.'/../../storage/framework/'.$class.'Model.php');
            file_put_contents(_DIR_.'/../../storage/framework/'.$class.'Model.php', $temp2);
            require_once(_DIR_.'/../../storage/framework/'.$class.'Model.php');
            unlink(_DIR_.'/../../storage/framework/'.$class.'Model.php'); */

            Cache::store('file')->setDirectory(_DIR_.'/storage/framework/cache/classes')
                ->plainPut(_DIR_.'/../../storage/framework/cache/classes/'.$class.'Model.php', $temp2);
            require_once(_DIR_.'/../../storage/framework/cache/classes/'.$class.'Model.php');


            /* if (file_exists(_DIR_.'/../../storage/framework/'.$class.'.php'))
                unlink(_DIR_.'/../../storage/framework/'.$class.'.php');
            file_put_contents(_DIR_.'/../../storage/framework/'.$class.'.php', $temp);
            require_once(_DIR_.'/../../storage/framework/'.$class.'.php');
            unlink(_DIR_.'/../../storage/framework/'.$class.'.php'); */

            Cache::store('file')->setDirectory(_DIR_.'/storage/framework/cache/classes')
                ->plainPut(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php', $temp);
            require_once(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php');
    
        }
        else
        {
            //echo "$newclass<br>";
            if (strpos($newclass, '/app/')!=false)
            {
                #echo "Saving $newclass in cache<br>";
                #var_dump( function_exists('callbackReplaceArrayStart') );
                #$temp = str_replace('=[', '= [', $temp);
                #$temp = preg_replace_callback('/([\W][^\]])\[/x', 'callbackReplaceArrayStart', $temp);
                #$temp = preg_replace_callback('/(array\([^\]|;]*)(\]|;*[\W]*\])/x', 'callbackReplaceArrayEnd', $temp);
                #$temp = str_replace('::class', '', preg_replace('/\w*::class/x', "'$0'", $temp));
                $temp = replaceNewPHPFunctions($temp);

                Cache::store('file')->setDirectory(_DIR_.'/storage/framework/cache/classes')
                    ->plainPut(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php', $temp);
                require_once(_DIR_.'/../../storage/framework/cache/classes/'.$class.'.php');

            }
            else
            {
                require_once($newclass);
            }
        }

    }
    /* else if ($newclass!='' && $version=='NEW')
    {
        require_once($newclass);
    } */
    
}


# MySQL Conector
$database = new Connector(env('DB_HOST'), env('DB_USER'), env('DB_PASSWORD'), env('DB_NAME'), env('DB_PORT'));


# Error handling
//set_exception_handler(array('ExceptionHandler', 'handleException'));


# Autologin
if (isset($_COOKIE[env('APP_NAME').'_token']) && !Auth::user() && Route::has('login'))
{
    Auth::autoLogin($_COOKIE[env('APP_NAME').'_token']);
}
