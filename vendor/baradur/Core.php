<?php

//session_destroy();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

# Global variables
$routes = array();
$observers = array();
$listeners = array();
$version = '';
$debuginfo = array();


#ini_set('memory_limit', '256M');

define ('_DIR_', str_replace('vendor/baradur', '', dirname(__FILE__)));

$_class_list = array();
$_model_list = array();
$_resource_list = array();
$_feature_list = array();
$_enum_list = array();
$_builder_methods = array();
$_invokable_list = array();

global $artisan;

# Load all classes
if (!file_exists(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'classes.php'))
{
    global $debuginfo, $artisan;
    $debuginfo['startup'] = 'Cache is empty. Creating all classes';

    $it = new RecursiveDirectoryIterator(_DIR_.'app');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $key = str_ireplace('.php', '', basename($file));

            $_class_list[$key] = str_replace(_DIR_, '', realpath($file));
            
            if (strpos(realpath($file), '/app/models/')>0)
                $_model_list[] = $key;
            
            if (strpos(realpath($file), '/app/resources/')>0)
                $_resource_list[] = $key;

            if (strpos(realpath($file), '/app/features/')>0)
                $_feature_list[] = $key;

        }
    }
    $it = new RecursiveDirectoryIterator(_DIR_.'database');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $key = str_ireplace('.php', '', basename($file));

            $_class_list[$key] = str_replace(_DIR_, '', realpath($file));
        }
    }

    $it = new RecursiveDirectoryIterator(_DIR_.'vendor/baradur');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $key = str_ireplace('.php', '', basename($file));
            
            if (!isset($_class_list[$key])) {
                $_class_list[$key] = str_replace(_DIR_, '', realpath($file));
            }
        } 
    }

    $it = new RecursiveDirectoryIterator(_DIR_.'vendor/faker');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
        {
            $key = str_ireplace('.php', '', basename($file));

            if (!isset($_class_list[$key])) {
                $_class_list[$key] = str_replace(_DIR_, '', realpath($file));
            }
        }
    }

    $it = null;

    @file_put_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'classes.php', serialize($_class_list));
    @file_put_contents(_DIR_.'storage/framework/config/models.php', serialize($_model_list));
    @file_put_contents(_DIR_.'storage/framework/config/resources.php', serialize($_resource_list));
    @file_put_contents(_DIR_.'storage/framework/config/features.php', serialize($_feature_list));
}
else
{
    global $debuginfo;
    $debuginfo['startup'] = 'All classes loaded from cache';

    $_class_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/'.($artisan? 'artisan_':'').'classes.php'));
    $_model_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/models.php'));
    $_resource_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/resources.php'));
    $_feature_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/features.php'));

}
if (file_exists(_DIR_.'storage/framework/config/enums.php')) {
    $_enum_list = unserialize(file_get_contents(_DIR_.'storage/framework/config/enums.php'));
}


# Autoload function registration
spl_autoload_register('baradur_class_loader');

# Application Configuration
$config = array();

if (!file_exists(_DIR_.'bootstrap/cache/config.php')) {
    require_once('DotEnv.php');
    DotEnv::load(_DIR_, '.env');
}

# Globals
require_once('Globals.php');

# Global functions / Router functions
require_once('Global_functions.php');
require_once('PHPConverter.php');
require_once('CoreLoader.php');

# The PHP Parser
$phpConverter = new PHPConverter();

# Default HOME constant
# (in case it's not defined in route provider)
define('HOME', '/');

if (!file_exists(_DIR_.'bootstrap/cache/config.php')) {
    CoreLoader::loadConfigFile(_DIR_.'config/app.php');
} else {
    $content = file_get_contents(_DIR_.'bootstrap/cache/config.php');
    $config = json_decode($content, true);
}

if (config('app.debug_info')) {
    global $debuginfo;
    $debuginfo['start'] = microtime(true);
}

# Error handling
error_reporting(E_ERROR + E_PARSE + E_CORE_ERROR + E_RECOVERABLE_ERROR 
    + E_USER_ERROR + E_COMPILE_ERROR /* + ~E_WARNING */);

# Display errors only in debug mode
if (config('app.debug')) {
    ini_set('display_errors', Str::startsWith(PHP_VERSION, '5'));
} else {
    ini_set('display_errors', false);
}


# Application Key Generator (for Tokens)
require_once(_DIR_.'vendor/random_compat/lib/random.php');

# Database global Connector
$database = null;

# Instantiating App
$app = new App();

# Instantiating singletons
$app->singleton('request', 'Request');
$app->singleton('session', 'RequestSession');
$app->singleton('_exceptionHandler', 'ExceptionHandler');

# Initializing timezone
setlocale(LC_ALL, config('app.locale'));
date_default_timezone_set(config('app.timezone'));

# Initializing App cache
$app_cache = new FileStore(new Filesystem(), _DIR_.'storage/framework/cache', 0777);
$appCached = $app_cache->get('Baradur_cache');
if (!$appCached) $appCached = array();


# Initializing Storage
Storage::$path = _DIR_.'storage/app/public/';

# Caching all classes
loadClassesInCache($_class_list);

# Initializing Autoload classes
if (file_exists(_DIR_.'vendor/autoload.php') && !$artisan) {

    $extra = include_once(_DIR_.'vendor/autoload.php');
    
    if (count($extra)>0) {
        foreach ($extra as $key => $val) {
            if (Str::startsWith($val, 'vendor/')) {
                require_once(_DIR_.$val);
            } else {
                require_once(_DIR_.'storage/framework/classes/'.basename($val));
            }
        }
    }
}

# Loading Providers
$_service_providers = array();
foreach(config('app.providers') as $provider) {
    CoreLoader::loadClass(_DIR_.'app/providers/'.$provider.'.php');
}

foreach (array_keys($_service_providers) as $provider) {
    $class = new $provider;
    $class->register();
    $_service_providers[$provider] = $class;
}

foreach ($_service_providers as $key => $provider) {
    $provider->boot();
}


# Autoload function
function baradur_class_loader($class, $require=true) 
{
    if (strpos($class, 'PHPExcel_')!==false) return;

    //if ($require) echo "Loading Baradur class: ".$class."<br>";
 
    $newclass = null;

    global $_class_list, $_invokable_list, $phpConverter;

    if (isset($_class_list[$class])) {
        $newclass = _DIR_ . $_class_list[$class];
    }
    
    if (file_exists(_DIR_.'storage/framework/classes/'.$class.'.php') && $newclass)
    {
        $date = filemtime($newclass);
        $cachedate = filemtime(_DIR_.'storage/framework/classes/'.$class.'.php');
        //echo($class.":::".$date ."::".$cachedate."<br>");

        if ($date < $cachedate)
        {
            if (!$require)
                return;
            
            //echo "Requiring class: $class <br>";
            require_once(_DIR_.'storage/framework/classes/'.$class.'.php');

            if (file_exists(_DIR_.'storage/framework/classes/baradurClosures_'.$class.'.php')) {
                require_once(_DIR_.'storage/framework/classes/baradurClosures_'.$class.'.php');
            }

            if (file_exists(_DIR_.'storage/framework/classes/baradurBuilderMacros_'.$class.'.php')) {
                require_once(_DIR_.'storage/framework/classes/baradurBuilderMacros_'.$class.'.php');
            }

            if (file_exists(_DIR_.'storage/framework/classes/baradurCollectionMacros_'.$class.'.php')) {
                require_once(_DIR_.'storage/framework/classes/baradurCollectionMacros_'.$class.'.php');
            }

            return;
        } 
        else
        {
            @unlink(_DIR_.'storage/framework/classes/'.$class.'.php');
        }
    }
    
    if ($newclass) // && $version=='OLD')
    {
        if (strpos($newclass, '/vendor')===false)
        {
            //echo "Caching class $newclass<br>";
            $temp = file_get_contents($newclass);

            /* if (preg_match('/public[\s]*function[\s]*__invoke\(/x', $temp))
            {
                $_invokable_list[$class] = $_class_list[$class];
            } */

            if (strpos($newclass, 'baradurClosures_')===false)
            {
                $temp = $phpConverter->replaceNewPHPFunctions($temp, $class, _DIR_);
            }
            else
            {
                $temp = $phpConverter->replaceStatics($temp);
            }

            //echo "Saving class $class<br>";

            Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/'.$class.'.php', $temp);
            $temp = null;
            
            if ($require) {
                require_once(_DIR_.'storage/framework/classes/'.$class.'.php');

                if (file_exists(_DIR_.'storage/framework/classes/baradurClosures_'.$class.'.php')) {
                    require_once(_DIR_.'storage/framework/classes/baradurClosures_'.$class.'.php');
                }
    
                if (file_exists(_DIR_.'storage/framework/classes/baradurBuilderMacros_'.$class.'.php')) {
                    require_once(_DIR_.'storage/framework/classes/baradurBuilderMacros_'.$class.'.php');
                }
    
                if (file_exists(_DIR_.'storage/framework/classes/baradurCollectionMacros_'.$class.'.php')) {
                    require_once(_DIR_.'storage/framework/classes/baradurCollectionMacros_'.$class.'.php');
                }
                
                return;
            }
        }
        elseif ($require)
        {
            require_once($newclass);
            return;
        }
    }
    
    if (!$require) {
        return;
    }

    throw new MissingClassException("Class [$class] not found");
}

function fataErrorHandler()
{
    if (env('EXIT_OK')) {
        exit();
    }

    $error = strip_tags(ob_get_clean());

    if (!config('app.debug')){
        echo View::showErrorTemplate(500, __('Server Error'));
        exit();
    }

    $etype = 'Fatal'; //Str::before($error, ' error:');
    $error = Str::after($error, ' error:');
    $error = explode(' in /', $error);
    $message = array_shift($error);

    if (strlen($message)==0 && !config('app.debug')) {
        abort(500);
    }

    $error = explode(' on line ', $error[0]);

    $file = '/'. $error[0];
    $line = $error[1];

    if (strpos($line, ' ')!==false) {
        $line = explode(' ', $line);
        $line = $line[0];
    }
    if (strpos($line, "\n")!==false) {
        $line = explode("\n", $line);
        $line = $line[0];
    }

    $blade = new BladeOne(_DIR_.'vendor/baradur/Exceptions/views', _DIR_.'storage/framework/views');

    $result = $blade->runInternal(
        'fatal', array(
            'etype' => $etype,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'content' => Helpers::loadFile($file, intval($line)-10, intval($line)+10)
        ),
        true
    );

    echo CoreLoader::processResponse(response($result, 500));

    exit();

}


if (!$artisan) {
    set_exception_handler(array('Handler', 'getInstance'));
    register_shutdown_function("fataErrorHandler");
}

if (config('app.debug')) {
    Route::get('framework/artisan/{command}', array('Artisan', 'runCommand'));
}

function loadClassesInCache($list)
{
    if (file_exists(_DIR_.'storage/framework/classes/loaded')) {
        return;
    }

    foreach ($list as $class => $location) {
        # Skip migration files and vendor classes
        if (strpos($location, 'database/migrations')===false && strpos($location, 'vendor/')===false)
        {
            baradur_class_loader($class, false);
        }
    }

    Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/loaded', '');
}
