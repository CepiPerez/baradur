<?php

class EnsureFeaturesAreActive
{
    protected static $callback;

    public function handle()
    {
        $parameters = func_get_args();

        $request = array_shift($parameters);
        
        array_shift($parameters);

        foreach($parameters as $feature) {
            if (!Feature::active($feature)) {
                if (self::$callback) {
                    list($class, $method) = getCallbackFromString(self::$callback);
                    executeCallback($class, $method, array(request(), $parameters));
                } else {
                    abort(400);
                }
            }
        }
        
        return $request;
    }

    public static function whenInactive($callback)
    {
        self::$callback = $callback;
    }



}
