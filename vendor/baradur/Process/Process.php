<?php

class Process
{
    //public static $pool = array();

    public static function run($command)
    {
        $process = new ProcessHandler();
        $process->run($command);
        return $process;
    }

    public static function start($command)
    {
        $process = new ProcessHandler();
        return $process->start($command);
    }

    public static function path($path)
    {
        $process = new ProcessHandler(array('procCwd' => $path));
        return $process;
    }

    public static function timeout($seconds)
    {
        $process = new ProcessHandler(array('timeout' => $seconds));
        return $process;
    }

    public static function forever()
    {
        $process = new ProcessHandler(array('timeout' => null));
        return $process;
    }

    public static function quietly()
    {
        $process = new ProcessHandler(array('quietly' => true));
        return $process;
    }


}