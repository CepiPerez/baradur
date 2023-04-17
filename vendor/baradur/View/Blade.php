<?php

class Blade
{
    private static $paths = array();
    private static $components = array();
    private static $compilers = array();

    public static function anonymousComponentPath($path, $namespace='default')
    {
        self::$paths[$namespace] = $path;
    }

    public static function component($component, $class)
    {
        self::$components[$component] = $class;
    }

    public static function render($template_string, $attributes)
    {
        $cache = _DIR_.'storage/framework/views';

        if ( !file_exists($cache) ) { 
            mkdir($cache); 
        }

        if (file_exists($cache.'/temp_view')) {
            unlink($cache.'/temp_view.blade.php');
        }

        file_put_contents($cache.'/temp_view.blade.php', $template_string);

        $blade = new BladeOne($cache, $cache);

		$result = $blade->run('temp_view', $attributes);

        return $result;
    }

    public static function _if($compiler, $callback)
    {
        self::$compilers[$compiler] = $callback;
    }

    public static function __findCompiler($compiler)
    {
        if (isset(self::$compilers[$compiler])) {
            return self::$compilers[$compiler];
        }

        return null;
    }

    public static function __findTemplate($template)
    {
        $dir = _DIR_ . 'resources/views/';

        if (file_exists($dir . str_replace('.', '/', $template) . '.blade.php')) { 
            return array($dir, $template);
        }

        if (file_exists($dir . str_replace('.', '/', $template) . '/index.blade.php')) {
            return array($dir, $template.'.index');
        }
        
        $array = explode('.', $template);        
        $template = array_pop($array);
        $namespace = 'default';
        $dir = self::$paths[$namespace];
        
        if (strpos($template, "::")!==false) {
            list($namespace, $template) = explode('::', $template);
        }

        if (file_exists($dir . '/' . str_replace('.', '/', $template) . '.blade.php')) {
            return array($dir, $template);
        }

        return null;
    }

    public static function __findComponent($component)
    {
        global $_class_list;

        if (isset(self::$components[$component])) {
            return self::$components[$component];
        }

        $result = ucfirst($component).'Component';

        if (array_key_exists($result, $_class_list)) {
            return $result;
        }

        return '';
    }

}