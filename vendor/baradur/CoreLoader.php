<?php

class CoreLoader
{

    public static function loadClass($file, $is_provider=true, $migration=null)
    {
        global $artisan, $_class_list;
        $cfname = str_replace('.php', '', str_replace('.PHP', '', basename($file)));

        $dest_folder = dirname(__FILE__).'/../../storage/framework/classes/';
        $dest_file = basename($file);

        if (file_exists($file))
        {
            if (
                !file_exists($dest_folder./* 'baradur_'. */$dest_file) 
                ||
                (filemtime($file) > filemtime($dest_folder./* 'baradur_'. */$dest_file))
                /* || 
                env('APP_DEBUG')==1 */)
            {
                //echo "Recaching file:". $file."<br>";

                $classFile = file_get_contents($file);

                if (strpos($cfname, 'baradurClosures_')===false)
                {
                    $classFile = replaceNewPHPFunctions($classFile, $cfname, _DIR_);
                }
                else
                {
                    $classFile = preg_replace_callback('/(\w*)::(\w*)/x', 'callbackReplaceStatics', $classFile);
                }

                if (isset($migration))
                {
                    $classFile = preg_replace('/return[\s]*new[\s]*class/', "class $migration ", $classFile);
                }
                
                if ($artisan)
                {
                    ini_set('display_errors', false);
                    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
                }
                
                if (strpos($cfname, 'baradurClosures_')===false && 
                    strpos($cfname, 'baradurBuilderMacros_')===false && 
                    strpos($cfname, 'baradurCollectionMacros_')===false)
                {
                    Cache::store('file')->plainPut($dest_folder.$dest_file, $classFile);
                    //require_once($dest_folder.$dest_file);
                }
                else
                {
                    Cache::store('file')->plainPut($dest_folder.$dest_file, $classFile);
                    //require_once($dest_folder.$dest_file);
                }
                
                //$classFile = null;

            }
            /* else
            { */
                if ($artisan)
                {
                    ini_set('display_errors', false);
                    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
                }

                //echo "Requiring class: $dest_file <br>";

                require_once($dest_folder.$dest_file);
                
                if (file_exists($dest_folder.'baradurClosures_'.$dest_file)) {
                    //echo "Requiring class: baradurClosures_$dest_file <br>";
                    require_once($dest_folder.'baradurClosures_'.$dest_file);
                }

                if (file_exists($dest_folder.'baradurBuilderMacros_'.$dest_file)) {
                    //echo "Requiring class: baradurBuilderMacros_$dest_file <br>";
                    require_once($dest_folder.'baradurBuilderMacros_'.$dest_file);
                }

                if (file_exists($dest_folder.'baradurCollectionMacros_'.$dest_file)) {
                    //echo "Requiring class: baradurCollectionMacros_$dest_file <br>";
                    require_once($dest_folder.'baradurCollectionMacros_'.$dest_file);
                }

            /* } */
            

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
            //||
            //(filemtime($file) > filemtime($dest_folder.$dest_file))
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
        return view($controller, $route->viewparams);
    }

    /* public static function invokeClass($route)
    {
        //echo "Invoking $route->controller :: $route->func";

        $reflectionMethod = new ReflectionMethod($route->controller, $route->func);
        
        return $reflectionMethod->invokeArgs($route->instance, $route->parametros);

    } */

    public static function invokeClassMethod($class, $method, $params=array(), $instance=null)
    {
        //dump($class);
        //$controller = $instance? $instance : new $class;
        
        if (is_subclass_of($instance, 'BaseController'))
        {
            foreach ($instance->middleware as $midd)
            {
                list($middelware, $parameters) = explode(':', $midd->middleware);

                $middelware = HttpKernel::getMiddlewareForController($middelware);
                $middelware = new $middelware;

                $res = request();

                if (isset($midd->only) && $midd->only==$method)
                    $res = $middelware->handle($res, null, $parameters);

                elseif (isset($midd->except) && $midd->except!=$method)
                    $res = $middelware->handle($res, null, $parameters);

                elseif (!isset($midd->except) && !isset($midd->only))
                    $res = $middelware->handle($res, null, $parameters);

                if (!($res instanceof Request))
                    return $res;
            }
        }
        
        $reflectionMethod = new ReflectionMethod($class, $method);       
        return $reflectionMethod->invokeArgs($instance, $params);

    }

    public static function processResponse($response)
    {
        //ddd($response);
        $status = 'HTTP/'.$response->protocol().' '.$response->status().' '.$response->reason();
        header($status);

        foreach ($response->headers() as $key => $val) {

            $val = is_array($val) ? reset($val) : $val;

            if ($key=='Location') {
                echo header($key. ": ". $val); 
                exit();
            }
            else {
                header($key. ": ". $val);
            }
        }

        if ($response->filename) {
            @readfile($response->filename);
            exit();
        }

        echo env('DEBUG_INFO') ?
            self::addDebugInfo($response->body()) :
            $response->body();
        
        exit();
    }

    private static function addDebugInfo($html)
    {
        global $debuginfo;
        $size = memory_get_usage();
        $debuginfo['memory_usage'] = get_memory_converted($size);
        $params['debug_info'] = $debuginfo;

        $start = $debuginfo['start'];
        $end = microtime(true) - $start;
        $debuginfo['time'] = number_format($end, 2) ." seconds";

        $script = '<script>var debug_info = '."[".json_encode($debuginfo)."]"."\n".
            '$(document).ready(function(e) {
                console.log("TIME: "+debug_info.map(a => a.time));
                console.log("MEMORY USAGE: "+debug_info.map(a => a.memory_usage));
                let q = debug_info.map(a => a.queryes);
                if (q[0]) {
                q[0].forEach(function (item, index) {
                    console.log("Query #"+(index+1));
                    console.log(item);
                });
                }
            });</script>';

        return str_replace('</body>', $script."\n".'</body>', $html);

    }


}