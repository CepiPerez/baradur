<?php

Class Storage
{
    public static $path;

    public static function getLinks()
    {
        $res = include(_DIR_.'/../../config/filesystem.php');
        return $res['links'];
    }

    public static function get($file)
    {
        return file_get_contents(self::$path.$file);
    }

    public static function exists($file)
    {
        return file_exists(self::$path.$file);
    }

    public static function missing($file)
    {
        return !file_exists(self::$path.$file);
    }

    public static function download($file, $name=null, $headers=null)
    {
        //$res = file_get_contents(self::$path.$file);
        if (!isset($name)) $name = basename($file);
        $mime = mime_content_type(self::$path.$file);

        header('Content-type: '.$mime);
        header('Content-disposition: download; filename="'.$name.'"');
        header('content-Transfer-Encoding:binary');
        header('Accept-Ranges:bytes');
        @readfile(self::$path.$file);
        exit();
    }

    public static function url($file)
    {
        return env('APP_URL').'/storage/'.$file;
    }

    public static function lastModified($file)
    {
        return filemtime(self::$path.$file);
    }

    public static function path($file)
    {
        return realpath(self::$path.$file);
        //return env('APP_URL').'/storage/'.$file;
    }


    public static function put($file, $contents)
    {
        if (is_object($contents) && get_class($contents)=='StorageFile')
        {
            return $contents->store($file);
        }

        $res = file_put_contents(self::$path.$file, $contents);

        if ($res) @chmod(self::$path.$file, 0777);

        return $res;
    }

    public static function copy($source, $dest)
    {
        return copy(self::$path.$source, self::$path.$dest);
    }

    public static function move($source, $dest)
    {
        return rename(self::$path.$source, self::$path.$dest);
    }

    public static function delete($file)
    {
        $ok = null;
        if (is_array($file))
        {
            foreach ($file as $f)
                $ok = Storage::delete($f);
        }
        else
        {
            $ok = unlink(self::$path.$file);
        }
        return $ok;

    }



}