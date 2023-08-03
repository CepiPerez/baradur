<?php

class Process
{

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

    public static function pipe($callback)
    {
        if (is_array($callback) && !is_closure($callback)) {
            return self::arrayPipe($callback);
        }

        if (!is_closure($callback)) {
            throw new Exception("Process::pipe() requires a valid callback.");
        }

        $process = new ProcessHandler();

        list($class, $method) = getCallbackFromString($callback);
		call_user_func_array(array($class, $method), array(new Pipe($process)));

        return $process;

    }

    private static function arrayPipe($pipes)
    {
        $process = new ProcessHandler();

        $output = null;

        foreach ($pipes as $command) {
            $output = $process->run($command, $output)->output();
        }

        return $process;

    }

}