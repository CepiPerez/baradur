<?php

class MiddlewareHelper
{
    protected static $kernel;
    protected static $booted = false;

    public static function bootKernel()
    {
        if (self::$booted)
            return;

        if (file_exists(_DIR_.'/../../app/http/Kernel.php'))
        {
            $temp = file_get_contents(_DIR_.'/../../app/http/Kernel.php');

            $temp = replaceNewPHPFunctions($temp, 'App_Http_Kernel', _DIR_);

            Cache::store('file')->plainPut(_DIR_.'/../../storage/framework/classes/App_Http_Kernel.php', $temp);
            require_once(_DIR_.'/../../storage/framework/classes/App_Http_Kernel.php');
            
            self::$kernel = new Kernel;
            self::$booted = true;
        }
    }

    public static function getMiddlewaresList()
    {
        return self::$kernel->getMiddlewareList();
    }

    public static function getMiddlewareGroup()
    {
        return self::$kernel->getMiddlewareGroup();
    }

    /* public static function invokeMiddleware($middleware, $request, $params)
    {
        //echo "Calling $middleware<br>";
        $controller = new $middleware;
        
        if ($middleware=='VerifyCsrfToken')
        {
            return $controller->_handleCsrf($request);
        }

        $params = array_merge(array($request, null), explode(',', $params));

        $reflectionMethod = new ReflectionMethod($middleware, 'handle');        
        return $reflectionMethod->invokeArgs($controller, $params);

    } */

}