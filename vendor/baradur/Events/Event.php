<?php

class Event
{

    public static function listen($event, $callback = null)
    {
        global $listeners;

        if (is_closure($event)) {
            
            list($c, $m) = getCallbackFromString($event);

            $reflectionMethod = new ReflectionMethod($c, $m);
            $paramNames = $reflectionMethod->getParameters();
            $param = $paramNames[0];

            $class = $param->getClass();

            $listeners[$class->getName()][] = $event;
        
            return;
        }
        
        $listeners[$event][] = $callback;

        
    }



}