<?php

class MiddlewareHelper
{
    protected static $kernel;

    public static function bootKernel()
    {
        if (file_exists(_DIR_.'/../../app/http/Kernel.php'))
        {
            $temp = file_get_contents(_DIR_.'/../../app/http/Kernel.php');

            $temp = replaceNewPHPFunctions($temp);

            Cache::store('file')->setDirectory(_DIR_.'/storage/framework/cache/classes')
                ->plainPut(_DIR_.'/../../storage/framework/cache/classes/App_Http_Kernel.php', $temp);
            require_once(_DIR_.'/../../storage/framework/cache/classes/App_Http_Kernel.php');
            
            self::$kernel = new Kernel;
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




}