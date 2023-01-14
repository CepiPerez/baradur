<?php

//session_destroy();


# Global variables
$routes = array();
$observers = array();
$version = '';
$debuginfo = array();

$preventLazyLoading = false;
$preventSilentlyDiscardingAttributes = false;
$preventAccessingMissingAttributes = false;


#ini_set('memory_limit', '256M');

ini_set('display_errors', 0);
error_reporting(E_ALL + E_NOTICE);
#ini_set('display_errors', false);
#error_reporting(0);

/* if (version_compare(phpversion(), '8.0.0', '>='))
{
    ini_set('display_errors', false);
    error_reporting(0);
} */

define ('_DIR_', str_replace('vendor/baradur', '', dirname(__FILE__)));

$_class_list = array();
$_model_list = array();
$_resource_list = array();
$_enum_list = array();
$_builder_methods = array();

global $artisan;

# Load all classes
if (!file_exists(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'classes.php'))
{
    //echo "CREATING CLASSES<br>";
    $it = new RecursiveDirectoryIterator(_DIR_.'app');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $_class_list[str_replace('.php', '', str_replace('.PHP', '', basename($file)))] = realpath($file);
            if (strpos(realpath($file), '/app/models/')>0)
                $_model_list[] = str_replace('.php', '', str_replace('.PHP', '', basename($file)));
            if (strpos(realpath($file), '/app/resources/')>0)
                $_resource_list[] = str_replace('.php', '', str_replace('.PHP', '', basename($file)));
        }
    }
    $it = new RecursiveDirectoryIterator(_DIR_.'database');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $_class_list[str_replace('.php', '', str_replace('.PHP', '', basename($file)))] = realpath($file);
        }
    }

    $it = new RecursiveDirectoryIterator(_DIR_.'vendor/baradur');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $_class_list[str_replace('.php', '', str_replace('.PHP', '', basename($file)))] = realpath($file);
        }
    }

    $it = new RecursiveDirectoryIterator(_DIR_.'vendor/faker');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $_class_list[str_replace('.php', '', str_replace('.PHP', '', basename($file)))] = realpath($file);
        }
    }

    if (file_exists(_DIR_.'vendor/autoload.php'))
    {
        $real = _DIR_;
        $real = rtrim($real, 'baradur');
        $extra = include_once(_DIR_.'vendor/autoload.php');
        if (count($extra)>0)
        {
            foreach ($extra as $key => $val)
                $_class_list[$key] = $real.$val;
        }
    }

    @file_put_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'classes.php', serialize($_class_list));
    @file_put_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'models.php', serialize($_model_list));
    @file_put_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'resources.php', serialize($_resource_list));
}
else
{
    $_class_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'classes.php'));
    $_model_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'models.php'));
    $_resource_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'resources.php'));
}


# Autoload function registration
spl_autoload_register('custom_autoloader');


# Environment variables
if (file_exists(_DIR_.'storage/framework/config/env.php'))
{
    require_once(_DIR_.'storage/framework/config/env.php');
}
else
{
    require_once('DotEnv.php');
    DotEnv::load(_DIR_, '.env');
}

# Globals
require_once('Globals.php');

if (env('DEBUG_INFO')==1)
{
    global $debuginfo;
    $debuginfo['start'] = microtime(true);
}

# Global functions / Router functions
require_once('Global_functions.php');
require_once('Routing/Route_functions.php');
require_once('CoreLoader.php');


# Generating Application KEY (for Tokens usage)
require_once(_DIR_.'vendor/random_compat/lib/random.php');
if (!isset($_SESSION['key']))
    $_SESSION['key'] = bin2hex(random_bytes(32));


# MySQL Conector
$database = null;

# Instantiating App
$app = new App();

# Instantiating Request
$app->singleton('request', 'Request');

# Including config file
$config = CoreLoader::loadConfigFile(_DIR_.'config/app.php');

# Initializing locale
$locale = $config['locale'];
$fallback_locale = $config['fallback_locale'];

# Initializing timezone
setlocale(LC_ALL, $config['locale']);
date_default_timezone_set($config['timezone']);

# Initializing App cache
$cache = new FileStore(new Filesystem(), _DIR_.'storage/framework/classes', 0777);

# Initializing Storage
Storage::$path = _DIR_.'storage/app/public/';

# Caching all classes
loadClassesInCache($_class_list);

# Loading Providers
$_service_providers = array();
foreach($config['providers'] as $provider)
{
    CoreLoader::loadClass(_DIR_.'app/providers/'.$provider.'.php');
}
foreach ($_service_providers as $provider)
{
    $class = new $provider;
    $class->register();
    unset($class);
}
foreach ($_service_providers as $provider)
{
    $class = new $provider;
    $class->boot();
    unset($class);
}


# Autoload function
function custom_autoloader($class, $require=true) 
{
    if (strpos($class, 'PHPExcel_')!==false) return;

    //echo "Loading Baradur class: ".$class."<br>";
 
    $newclass = '';

    global $_class_list;

    if (isset($_class_list[$class]))
        $newclass = $_class_list[$class];
    else
        return false;

    if (file_exists(_DIR_.'storage/framework/classes/'.$class.'.php'))
    {
        $date = filemtime($newclass);
        $cachedate = filemtime(_DIR_.'storage/framework/classes/'.$class.'.php');
        //echo($class.":::".$date ."::".$cachedate."<br>");

        if ($date < $cachedate) // && env('APP_DEBUG')==0)
        {
            if (file_exists(_DIR_.'storage/framework/classes/baradurClosures_'.$class.'.php') && $require)
                require_once(_DIR_.'storage/framework/classes/baradurClosures_'.$class.'.php');

            if ($require)
                require_once(_DIR_.'storage/framework/classes/'.$class.'.php');

            return;
        } 
        else
        {
            @unlink(_DIR_.'storage/framework/classes/'.$class.'.php');
        }
    }
    
    if ($newclass!='') // && $version=='OLD')
    {
        if (strpos($newclass, '/vendor/')===false)
        {
            //echo "Caching class $newclass<br>";
            $temp = file_get_contents($newclass);

            if (strpos($newclass, 'baradurClosures_')===false)
            {
                $temp = replaceNewPHPFunctions($temp, $class, _DIR_);
            }
            else
            {
                $temp = preg_replace_callback('/(\w*)::(\w*)/x', 'callbackReplaceStatics', $temp);
            }

            //echo "Saving class $class<br>";

            Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/'.$class.'.php', $temp);
            $temp = null;
            
            if ($require)
                require_once(_DIR_.'storage/framework/classes/'.$class.'.php');
        }
        elseif ($require)
        {
            require_once($newclass);
        }
    }
    
    
}

#dd("HOLA");

# Error handling
if (!$artisan) {
    set_exception_handler(array('ExceptionHandler', 'handleException'));
}


function loadClassesInCache($list)
{
    //dd($list);
    if (file_exists(_DIR_.'storage/framework/classes/loaded'))
    {
        return;
    }

    foreach ($list as $class => $location)
    {
        # Skip migration files and vendor classes
        if (strpos($location, 'database/migrations')===false && strpos($location, 'vendor/')===false)
        {
            custom_autoloader($class, false);
        }
    }

    Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/loaded', '');

}
