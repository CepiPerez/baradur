<?php

/**
 * @method static Feature for(string $scope)
*/

class Feature
{
    protected static $features = array();

    public static function define($feature, $callback)
    {
        self::$features[$feature] = $callback;
    }

    public static function instanceFor($scope=null)
    {
        $instance = new FeatureInstance($scope);

        return $instance;
    }

    public static function __getFeature($feature)
    {
        return self::$features[$feature];
    }

    public static function __getFeatures()
    {
        return self::$features;
    }

    public static function active($feature)
    {
        return self::instanceFor()->active($feature);
    }

    public static function inactive($feature)
    {
        return self::instanceFor()->inactive($feature);
    }

    public static function allAreActive($features)
    {
        return self::instanceFor()->allAreActive($features);
    }

    public static function someAreActive($features)
    {
        return self::instanceFor()->someAreActive($features);
    }

    public static function allAreInactive($features)
    {
        return self::instanceFor()->allAreInactive($features);
    }

    public static function someAreInactive($features)
    {
        return self::instanceFor()->someAreInactive($features);
    }

    public static function when($feature, $callback_true, $callback_false=null)
    {
        return self::instanceFor()->when($feature, $callback_true, $callback_false);
    }

    public static function unless($feature, $callback_true, $callback_false=null)
    {
        return self::instanceFor()->unless($feature, $callback_true, $callback_false);
    }
    
    public static function value($feature)
    {
        return self::instanceFor()->value($feature);
    }

    public static function values($features)
    {
        return self::instanceFor()->values($features);
    }

    public static function all()
    {
        return self::instanceFor()->all();
    }

    public static function activate($feature)
    {
        return self::instanceFor()->activate($feature);
    }

    public static function deactivate($feature)
    {
        return self::instanceFor()->deactivate($feature);
    }

    public static function forget($feature)
    {
        return self::instanceFor()->forget($feature);
    }

    public static function activateForEveryone($feature, $value=null)
    {
        return self::instanceFor()->activateForEveryone($feature, $value);
    }

    public static function deactivateForEveryone($feature)
    {
        return self::instanceFor()->deactivateForEveryone($feature);
    }

    public static function purge($features=null)
    {
        return self::instanceFor()->purge($features);
    }

    public static function flushCache()
    {
        global $app_cache, $appCached;

        $appCached['features'] = array();
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }

}