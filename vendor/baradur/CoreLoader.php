<?php

class CoreLoader
{

    public static function loadClass($file, $is_provider=true, $migration=null)
    {
        global $artisan, $_class_list, $phpConverter;
        $cfname = Str::replace('.php', '', basename($file), false);

        $dest_folder = str_replace('/vendor/baradur', '', dirname(__FILE__)).'/storage/framework/classes/';
        $dest_file = basename($file);

        if (file_exists($file))
        {
            if (!file_exists($dest_folder.$dest_file) || (filemtime($file) > filemtime($dest_folder.$dest_file)))
            {
                //echo "Recaching file:". $file."<br>";

                $classFile = file_get_contents($file);

                if (strpos($cfname, 'baradurClosures_')===false) {
                    $classFile = $phpConverter->replaceNewPHPFunctions($classFile, $cfname, _DIR_, $migration!==null);
                } else {
                    $classFile = $phpConverter->replaceStatics($classFile);
                }

                $folder = str_replace(_DIR_, '', dirname($file));
                $classFile = $phpConverter->replaceRequired($classFile, $folder);

                if (isset($migration)) {
                    $classFile = preg_replace('/return[\s]*new[\s]*[cC]lass/', "class $migration ", $classFile);
                }

                if ($artisan) {
                    ini_set('display_errors', false);
                    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
                }
                
                if (strpos($cfname, 'baradurClosures_')===false && 
                    strpos($cfname, 'baradurBuilderMacros_')===false && 
                    strpos($cfname, 'baradurCollectionMacros_')===false) {
                    Cache::store('file')->plainPut($dest_folder.$dest_file, $classFile);
                } else {
                    Cache::store('file')->plainPut($dest_folder.$dest_file, $classFile);
                }
                
            }

            if ($artisan) {
                ini_set('display_errors', false);
                error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
            }
            
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

            require_once($dest_folder.$dest_file);


            if ($is_provider) {
                global $_service_providers;
                $_service_providers[$cfname] = null;
            }
                    
        }
    }

    public static function loadConfigFile($file, $include=true)
    {
        global $phpConverter, $config;

        $dest_folder = dirname(__FILE__).'/../../storage/framework/config/';
        $path_parts = pathinfo($file);

        $filename = Str::replace('.php', '', $path_parts['basename'], false);

        if (!empty($config) && array_key_exists($filename, $config)) {
            return $config[$filename];
        }

        $classFile = file_get_contents($file);

        $classFile = $phpConverter->replaceNewPHPFunctions($classFile);

        Cache::store('file')->plainPut($dest_folder.$path_parts['basename'], $classFile);

        $result = include($dest_folder.$path_parts['basename']);

        if ($include) {
            $config[$filename] = $result;
        }

        $classFile = null;

        return $result;

    }

    public static function invokeView($route)
    {
        $controller = $route->view;
        if ($route->parametros) {
            for ($i=0; $i < count($route->parametros); ++$i) {
                $controller = str_replace($route->orig_parametros[$i], $route->parametros[$i], $controller);
            }
        }

        return view($controller, $route->viewparams);
    }

    public static function invokeClassMethod($class, $method, $params=array(), $instance=null)
    {        
        if (is_subclass_of($instance, 'BaseController')) {

            foreach ($instance->middleware as $midd) {
                list($middelware, $parameters) = explode(':', $midd->middleware);

                $middelware = HttpKernel::getMiddlewareForController($middelware);
                $middelware = new $middelware;

                $res = request();

                if (isset($midd->only) && $midd->only==$method) {
                    $res = $middelware->handle($res, null, $parameters);
                } elseif (isset($midd->except) && $midd->except!=$method) {
                    $res = $middelware->handle($res, null, $parameters);
                } elseif (!isset($midd->except) && !isset($midd->only)) {
                    $res = $middelware->handle($res, null, $parameters);
                }

                if (!($res instanceof Request)) {
                    return $res;
                }
            }
        }
        
        $reflectionMethod = new ReflectionMethod($class, $method);       
        return $reflectionMethod->invokeArgs($instance, $params);

    }

    public static function processResponse($response)
    {
        $status = 'HTTP/'.$response->protocol().' '.$response->status().' '.$response->reason();
        header($status);

        foreach ($response->headers() as $key => $val) {

            $val = is_array($val) ? reset($val) : $val;

            if ($key=='Location') {
                echo header($key. ": ". $val); 
                __exit();
            } else {
                header($key. ": ". $val);
            }
        }

        if ($response->filename) {
            @readfile($response->filename);
            __exit();
        }

        echo config('app.debug_info')
            ? self::addDebugInfo($response->body())
            : $response->body();
        
        __exit();
    }

    private static function addDebugInfo($html)
    {

        global $debuginfo;
        $size = function_exists('memory_get_usage') ? memory_get_usage() : 0;
        $debuginfo['memory_usage'] = get_memory_converted($size);
        $params['debug_info'] = $debuginfo;

        $start = $debuginfo['start'];
        $end = microtime(true) - $start;
        $debuginfo['time'] = number_format($end, 2) ." seconds";

        $script = '<script>var debug_info = '."[".json_encode($debuginfo)."]"."\n".
            '$(document).ready(function(e) {
                console.log("TIME: "+debug_info.map(a => a.time));
                console.log("MEMORY USAGE: "+debug_info.map(a => a.memory_usage));
                console.log("CACHE: "+debug_info.map(a => a.startup));
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