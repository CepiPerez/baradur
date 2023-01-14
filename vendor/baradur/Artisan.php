<?php

class Artisan
{
    
    public static function call($command)
    {
        return shell_exec('cd ' . _DIR_ . '; php artisan --fromweb ' . $command);
    }


}