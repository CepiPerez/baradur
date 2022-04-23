<?php

//session_destroy();

# This might be only necessary for local development using Docker
date_default_timezone_set('America/Argentina/Buenos_Aires');

# Global variables
$routes = array();
$middlewares = array();
$observers = array();
$version = '';

ini_set('display_errors', true);
error_reporting(E_ALL + E_NOTICE);
#ini_set('display_errors', false);

if (version_compare(phpversion(), '8.0.0', '>='))
{
    ini_set('display_errors', false);
    error_reporting(0);
}

define ('_DIR_', dirname(__FILE__));

# Autoload function registration
spl_autoload_register('custom_autoloader');


# Enviroment variables
require_once('DotEnv.php');
DotEnv::load(_DIR_.'/../../.env');

# Globals
require_once('Globals.php');


# Global functions
require_once('Global_functions.php');


# Generating Application KEY (for Tokens usage)
require_once(_DIR_.'/../random_compat/lib/random.php');
if (!isset($_SESSION['key']))
    $_SESSION['key'] = bin2hex(random_bytes(32));


# Instantiating App
$app = new App();

# Startup services
$config = new Config;
$config->boot();

# Routes
include(_DIR_.'/../../routes/routes.php');

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

    
    if ($newclass!='' && $version=='OLD')
    {
        $temp = file_get_contents($newclass);
        $temp = str_replace('  ', ' ', $temp);
        if (strpos($temp, ' extends Model')>0)
        {
            //echo "Class ".$class.' is Model's subclass!<br>';

            $temp2 = file_get_contents(_DIR_.'/Database/Model.php');
            $temp2 = str_replace('Model', $class.'Model', $temp2);
            $temp2 = str_replace('myparent', $class, $temp2);

            $temp = str_replace('extends Model', 'extends '.$class.'Model', $temp);

            $pattern = "/scope(.*)\(/i";
            if (preg_match_all($pattern, $temp, $matches))
            {
                $temp2 = rtrim($temp2, '}');
                foreach ($matches[1] as $scope)
                {
                    $temp2 .= "\n   public static function ". lcfirst($scope) ."()
    {
        return self::getInstance()->getQuery()->callScope('". lcfirst($scope) ."', func_get_args());
    }";
                }
                $temp2 .= "\n}";
            }


            if (file_exists(_DIR_.'/../../resources/_system/'.$class.'Model.php'))
                unlink(_DIR_.'/../../resources/_system/'.$class.'Model.php');
            file_put_contents(_DIR_.'/../../resources/_system/'.$class.'Model.php', $temp2);
            require_once(_DIR_.'/../../resources/_system/'.$class.'Model.php');
            unlink(_DIR_.'/../../resources/_system/'.$class.'Model.php');

            if (file_exists(_DIR_.'/../../resources/_system/'.$class.'.php'))
                unlink(_DIR_.'/../../resources/_system/'.$class.'.php');
            file_put_contents(_DIR_.'/../../resources/_system/'.$class.'.php', $temp);
            require_once(_DIR_.'/../../resources/_system/'.$class.'.php');
            unlink(_DIR_.'/../../resources/_system/'.$class.'.php');

    
        }
        else
        {
            require_once($newclass);
        }

    }
    else if ($newclass!='' && $version=='NEW')
    {
        require_once($newclass);
    }
    
}


# MySQL Conector
$database = new Connector(env('DB_HOST'), env('DB_USER'), env('DB_PASSWORD'), env('DB_NAME'), env('DB_PORT'));


# Error handling
//set_exception_handler(array('ExceptionHandler', 'handleException'));


# Autologin
if (isset($_COOKIE[env('APP_NAME').'_token']) && !Auth::user())
{
    Auth::autoLogin($_COOKIE[env('APP_NAME').'_token']);
}
