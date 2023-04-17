<?php

class File
{
    protected $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function getRealPath()
    {
        return $this->path;
    }

    public function hashName()
    {
        $res = pathinfo($this->path);

        return hash('md5', $this->path) . '.' . $res['extension'];
    }

    private static function instance()
    {
        return new Filesystem(_DIR_, null, null);
    }

    public static function get($path, $lock = false)
    {
        return self::instance()->get($path, $lock);
    }

    public static function json($path, $flags = 0, $lock = false)
    {
        return self::instance()->json($path, $flags, $lock);
    }

    public static function size($path)
    {
        return self::instance()->size($path);
    }

    public static function hash($path, $algorithm='md5')
    {
        return self::instance()->hash($path, $algorithm);
    }

    public static function type($path)
    {
        return self::instance()->type($path);
    }

    public static function isFile($path)
    {
        return self::instance()->isFile($path);
    }

    public static function isDirectory($path)
    {
        return self::instance()->isDirectory($path);
    }

    public static function delete($path)
    {
        return self::instance()->delete($path);
    }

    public static function put($path, $contents, $lock = false)
    {
        return self::instance()->put($path, $contents, $lock);
    }

    public static function chmod($path, $mode = null)
    {
        return self::instance()->chmod($path, $mode);
    }

    public static function exists($path)
    {
        return self::instance()->exists($path);
    }

    public static function missing($path)
    {
        return self::instance()->missing($path);
    }

    public static function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        return self::instance()->makeDirectory($path, $mode, $recursive, $force);
    }

    public static function deleteDirectory($path, $preserve = false)
    {
        return self::instance()->deleteDirectory($path, $preserve);
    }

    public static function download($file, $name=null, $headers=null)
    {
        return self::instance()->download($file, $name, $headers);
    }

    public static function lastModified($path)
    {
        return self::instance()->lastModified($path);
    }

    public static function copy($source, $dest)
    {
        return self::instance()->copy($source, $dest);
    }

    public static function move($source, $dest)
    {
        return self::instance()->move($source, $dest);
    }

    public static function putFile($path, $file = null, $options = array())
    {
        return self::instance()->putFile($path, $file, $options);
    }

    public static function putFileAs($path, $file, $name = null, $options = array())
    {
        return self::instance()->putFileAs($path, $file, $name , $options);
    }


}