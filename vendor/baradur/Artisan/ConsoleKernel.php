<?php

class ConsoleKernel
{
    protected $commands = array();

    protected static $kernel = null;

    private static function bootKernel()
    {
        global $phpConverter;

        if (!file_exists(_DIR_.'app/console/Kernel.php'))
        {
            throw new RuntimeException("Error trying to book Console kernel");
        }

        $temp = file_get_contents(_DIR_.'app/console/Kernel.php');

        $temp = str_replace('__DIR__', "_DIR_.'app/console'", $temp);
        $temp = $phpConverter->replaceNewPHPFunctions($temp, 'App_Http_Kernel', _DIR_);

        Cache::store('file')->plainPut(_DIR_.'storage/framework/classes/App_Console_Kernel.php', $temp);
        require_once(_DIR_.'storage/framework/classes/App_Console_Kernel.php');
        
        self::$kernel = new Kernel;
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

        foreach(new RecursiveIteratorIterator($it) as $file)
        {
            if (substr(basename($file), -4)=='.php' || substr(basename($file), -4)=='.PHP')
            {
                $key = str_replace('.php', '', str_replace('.PHP', '', basename($file)));

                $this->commands[] = $key;
            }
        }
        
    }

    public function getCommands()
    {
        return $this->commands;        
    }

}