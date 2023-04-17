<?php

class ConsoleKernel
{
    protected $commands = array();

    protected static $kernel = null;

    public static function __addCommand($command, $callback)
    {
        $res = self::getKernel();
        
        $result = new Command();
        $result->setSignature($command);
        $result->setCallback($callback);

        $res->commands[] = $result;
        
        return $result;
    }

    private static function bootKernel()
    {
        global $phpConverter;

        if (!file_exists(_DIR_.'app/console/Kernel.php')) {
            throw new RuntimeException("Error trying to book Console kernel");
        }

        $temp = file_get_contents(_DIR_.'app/console/Kernel.php');

        $classname = 'AppConsoleKernel';

        $temp = str_replace('__DIR__', "_DIR_.'app/console'", $temp);
        $temp = str_replace(' Kernel extends', " $classname extends", $temp);
        //$temp = str_replace('require ', '//require', $temp);
        $temp = preg_replace(
            '/(require|require_once|include|include_once)[\s](.*);/x', 
            'CoreLoader::loadClass($2, false);', 
            $temp
        );
        $temp = $phpConverter->replaceNewPHPFunctions($temp, 'App_Http_Kernel', _DIR_);        

        Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/App_Console_Kernel.php', $temp);
        require_once(_DIR_.'storage/framework/classes/App_Console_Kernel.php');

        self::$kernel = new $classname;

        //CoreLoader::loadClass(_DIR_.'routes/console.php', false);
    }

    public static function getKernel()
    {
        if (!self::$kernel) {
            self::bootKernel();
        }

        return self::$kernel;
    }

    public function loadCommands()
    {
        $this->commands();
    }

    public function load($path)
    {
        $it = new RecursiveDirectoryIterator($path);

        foreach(new RecursiveIteratorIterator($it) as $file) {
            if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP') {
                $key = str_ireplace('.php', '', basename($file));

                $this->commands[] = new $key;
            }
        }
    }

    public function getCommands()
    {
        return $this->commands;        
    }

}