<?php

class CoreLoader
{

    public static function loadProvider($file)
    {
        global $_closures, $_currentClosureFile;
        $cfname = str_replace('.php', '', str_replace('.PHP', '', basename($file)));

        $dest_folder = dirname(__FILE__).'/../../storage/framework/cache/classes/';
        $dest_file = basename($file);

        if (file_exists($file))
        {
            if (
                !file_exists($dest_folder.'baradur_'.$dest_file) 
                ||
                (filemtime($file) > filemtime($dest_folder.'baradur_'.$dest_file))
                || 
                env('APP_DEBUG')==1 )
            {
                //echo "Recaching file:". $file."<br>";

                $classFile = file_get_contents($file);

                # Closures
                $_currentClosureFile = $cfname;
                $pattern = '/,[\s]*[\S]*function[\s\S]*?}[\s]*\)/x';
                $classFile = preg_replace_callback($pattern, 'callbackReplaceClosures', $classFile);
                $_currentClosureFile = null;

                # Group closures
                $pattern = '/->routes[\s]*[\S]*\([\s]*[\S]*function[\s\S]*?}[\s]*\)/x';
                $classFile = preg_replace_callback($pattern, 'callbackReplaceGroupClosuresInProvider', $classFile);

                $classFile = replaceNewPHPFunctions($classFile);


                if (count($_closures)>0)
                {

                    $controller = "<?php\n\nclass baradurClosures_$cfname {\n\n";
                    foreach ($_closures as $closure)
                    {
                        $closure = rtrim( ltrim($closure, ","), ")");
                        $controller .= "\tpublic function ".$closure."\n\n";
                    }
                    $controller .= "}";

                    $_closures = array();

                    Cache::store('file')->setDirectory($dest_folder)
                        ->plainPut($dest_folder.'baradurClosures_'.$cfname.'.php', $controller);

                    include($dest_folder.'baradurClosures_'.$dest_file);

                }
                
                Cache::store('file')->setDirectory($dest_folder)
                    ->plainPut($dest_folder.'baradur_'.$dest_file, $classFile);

            }
            
            include($dest_folder.'baradur_'.$dest_file);

            $provider = new $cfname;
            $provider->register();
            $provider->boot();
            //eturn $provider;
            
        }

    }

    public static function loadConfigFile($file)
    {
        $dest_folder = dirname(__FILE__).'/../../storage/framework/config/';
        $dest_file = basename($file);

        if (
            !file_exists($dest_folder.$dest_file) 
            ||
            (filemtime($file) > filemtime($dest_folder.$dest_file))
            || 
            env('APP_DEBUG')==1 )
        {

            $classFile = file_get_contents($file);

            $classFile = replaceNewPHPFunctions($classFile);


            Cache::store('file')->setDirectory($dest_folder)
                ->plainPut($dest_folder.$dest_file, $classFile);
        }

        return include($dest_folder.$dest_file);

    }


}