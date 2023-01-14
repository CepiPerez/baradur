<?php

class MiddlewareHelper
{
    protected static $kernel;
    protected static $booted = false;
    protected static $list = null;

    public static function bootKernel()
    {
        if (self::$booted)
            return;

        if (file_exists(_DIR_.'app/http/Kernel.php'))
        {
            $temp = file_get_contents(_DIR_.'app/http/Kernel.php');

            $temp = replaceNewPHPFunctions($temp, 'App_Http_Kernel', _DIR_);

            Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/App_Http_Kernel.php', $temp);
            require_once(_DIR_.'storage/framework/classes/App_Http_Kernel.php');
            
            self::$kernel = new Kernel;
            self::$booted = true;
        }
    }

    private static function fillMiddlewareList()
    {
        $lists = array('middleware', 'middlewareGroups', 'routeMiddleware');
        
        foreach ($lists as $list)
        {
            self::$list[$list] = self::$kernel->getMiddlewareList($list);
        }
        //dump(self::$list);
    }

    private static function checkMiddlewareList()
    {
        if (!self::$list)
        {
            MiddlewareHelper::bootKernel();
            self::fillMiddlewareList();
        }
    }

    public static function getMiddlewareList($values)
    {
        self::checkMiddlewareList();
        
        $values = array_merge(self::$list['middleware'], $values);

        $final_list = array();

        foreach ($values as $value)
        {
            foreach (self::getMiddlewareFromValue($value) as $to_add)
            {
                $final_list[] = $to_add;
            }
        }

        return $final_list;

    }

    public static function getMiddlewareFromValue($value)
    {
        global $_class_list;

        list($midd, $params) = explode(':', $value);

        if (isset($_class_list[$midd]))
        {
            return array( $midd . ($params? ':'.$params : '') );
        }

        $list = array();

        $new_list = self::getMiddlewareListFromGroups($value);

        foreach ($new_list as $to_add)
        {
            $list[] = $to_add;
        }        

        return $list;    
    }
   
    private static function getMiddlewareListFromGroups($value)
    {
        self::checkMiddlewareList();

        global $_class_list;
        
        $list = array();

        list($midd, $params) = explode(':', $value);

        if (isset(self::$list['middlewareGroups'][$midd]))
        {
            $items = self::$list['middlewareGroups'][$midd];
            
            if (!is_array($items)) $items = array($items);

            foreach ($items as $item)
            {
                if (isset($_class_list[$item]))
                {
                    $list[] = $item . ($params? ':'.$params : '');
                }
            }
        }

        if (isset(self::$list['routeMiddleware'][$midd]))
        {
            $items = self::$list['routeMiddleware'][$midd];
            
            if (!is_array($items)) $items = array($items);

            foreach ($items as $item)
            {
                if (isset($_class_list[$item]))
                {
                    $list[] = $item . ($params? ':'.$params : '');
                }
            }
        }

        return $list;

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