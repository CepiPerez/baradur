<?php

Class HttpKernel
{
    protected $middleware = array();
    protected $middlewareAliases = array();
    protected $middlewareGroups = array();
    protected static $kernel = null;

    private static function bootKernel()
    {
        global $phpConverter;

        if (!file_exists(_DIR_.'app/http/Kernel.php')) {
            throw new RuntimeException("Error trying to book Http kernel");
        }

        $temp = file_get_contents(_DIR_.'app/http/Kernel.php');

        $temp = $phpConverter->replaceNewPHPFunctions($temp, 'App_Http_Kernel', _DIR_);

        Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/App_Http_Kernel.php', $temp);
        require_once(_DIR_.'storage/framework/classes/App_Http_Kernel.php');
        
        self::$kernel = new Kernel;
    }

    /** @return Kernel */
    private static function getKernel()
    {
        if (!self::$kernel) {
            self::bootKernel();
        }

        return self::$kernel;
    }

    public static function getMiddlewareForController($middleware)
    {
        $kernel = self::getKernel();

        $list = $kernel->getMiddlewareFromValue($middleware);

        if (count($list)!=1) {
            throw new RuntimeException("Error trying to load [$middleware] middleware.");
        }

        return reset($list);

    }

    public static function getMiddlewareList($values=array())
    {
        $kernel = self::getKernel();
        
        $values = array_merge($kernel->middleware, $values);

        $final_list = array();

        foreach ($values as $value) {
            $items = $kernel->getMiddlewareFromValue($value);
            $final_list = array_merge($final_list, $items);
        }

        return $final_list;

    }

    private function getMiddlewareFromValue($value)
    {
        global $_class_list;

        list($midd, $params) = explode(':', $value);

        if (isset($_class_list[$midd])) {
            return array( $midd . ($params? ':'.$params : '') );
        }

        $list = array();

        $items = $this->getMiddlewareListFromGroups($value);

        $list = array_merge($list, $items);

        return $list;    
    }

    private function getMiddlewareListFromGroups($middleware)
    {        
        $list = array();

        if (isset($this->middlewareGroups[$middleware])) {
            $items = $this->middlewareGroups[$middleware];
            
            foreach ($items as $item) {
                $result = $this->getMiddlewareListFromRouteGroup($item);

                if ($result) {
                    $list[] = $result;
                }
            }
        } else {
            $list[] = $this->getMiddlewareListFromRouteGroup($middleware);
        }
        
        return $list;
    }

    private function getMiddlewareListFromRouteGroup($value)
    {
        global $_class_list;

        list($item, $params) = explode(':', $value);
        
        if (isset($_class_list[$item])) {
            return $item . ($params? ':'.$params : '');
        }

        if (isset($this->middlewareAliases[$item])) {   
            return $this->middlewareAliases[$item] . ($params? ':'.$params : '');
        }

        return null;
    }


}