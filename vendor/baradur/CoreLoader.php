<?php

class CoreLoader
{

    public static function loadClass($file, $is_provider=true)
    {
        global $artisan, $_class_list;
        $cfname = str_replace('.php', '', str_replace('.PHP', '', basename($file)));

        $dest_folder = dirname(__FILE__).'/../../storage/framework/classes/';
        $dest_file = basename($file);

        if (file_exists($file))
        {
            if (
                !file_exists($dest_folder.'baradur_'.$dest_file) 
                ||
                (filemtime($file) > filemtime($dest_folder.'baradur_'.$dest_file))
                || 
                env('APP_ENV')!='production' )
            {
                //echo "Recaching file:". $file."<br>";

                $classFile = file_get_contents($file);

                if (strpos($cfname, 'baradurClosures_')===false)
                {
                    $classFile = replaceNewPHPFunctions($classFile, $cfname, _DIR_);
                }
                else
                {
                    $classFile = preg_replace_callback('/(\w*)::(\w*)/x', 'callbackReplaceModels', $classFile);
                }
                
                
                Cache::store('file')->plainPut($dest_folder.'baradur_'.$dest_file, $classFile);

                require_once($dest_folder.'baradur_'.$dest_file);
                
                if ($artisan)
                {
                    ini_set('display_errors', false);
                    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
                }

                $classFile = null;

            }
            else
            {
                if (file_exists($dest_folder.'baradurClosures_'.$dest_file))
                    require_once($dest_folder.'baradurClosures_'.$dest_file);

                require_once($dest_folder.'baradur_'.$dest_file);
            }
            

            if ($is_provider)
            {
                /* $provider = new $cfname;
                $provider->register();
                $provider->boot(); */
                global $_service_providers;
                $_service_providers[] = $cfname;
            }
            
            
        }

    }

    public static function loadConfigFile($file)
    {
        global $artisan;

        $dest_folder = dirname(__FILE__).'/../../storage/framework/config/';
        $dest_file = basename($file);

        if (
            !file_exists($dest_folder.$dest_file) 
            ||
            (filemtime($file) > filemtime($dest_folder.$dest_file))
            /* || 
            env('APP_ENV')!='production'  */)
        {

            $classFile = file_get_contents($file);

            $classFile = replaceNewPHPFunctions($classFile);

            Cache::store('file')->plainPut($dest_folder.$dest_file, $classFile);

            $classFile = null;
        }

        return include($dest_folder.$dest_file);

    }

    /* private static function getItemClass($item)
    {
        return $item->getClass()!=null ? $item->getClass()->getName() : null;
    } */


    public static function invokeView($route)
    {
        $controller = $route->view;
        if ($route->parametros)
        {
            for ($i=0; $i < count($route->parametros); ++$i)
            {
                $controller = str_replace($route->orig_parametros[$i], $route->parametros[$i], $controller);
            }
        }
        return view($controller);
    }

    /* public static function invokeClass($route)
    {
        //echo "Invoking $route->controller :: $route->func";

        $reflectionMethod = new ReflectionMethod($route->controller, $route->func);
        
        return $reflectionMethod->invokeArgs($route->instance, $route->parametros);

    } */

    public static function invokeClassMethod($class, $method, $params=array(), $instance=null)
    {

        $controller = $instance? $instance : new $class();

        if (is_subclass_of($controller, 'BaseController'))
        {
            foreach ($controller->middleware as $midd)
            {
                $class = $midd->findMiddlewareClass($midd->middleware);

                $res = request();

                $params = array_merge(array($res, null), array());

                if (isset($midd->only) && $midd->only==$method)
                    $res = self::invokeClassMethod($class, 'handle', $params);

                elseif (isset($midd->except) && $midd->except!=$method)
                    $res = self::invokeClassMethod($class, 'handle', $params);

                elseif (!isset($midd->except) && !isset($midd->only))
                    $res = self::invokeClassMethod($class, 'handle', $params);

                if (!($res instanceof Request))
                    return $res;
            }
        }
        
        $reflectionMethod = new ReflectionMethod($controller, $method);        
        return $reflectionMethod->invokeArgs($controller, $params);

    }


}