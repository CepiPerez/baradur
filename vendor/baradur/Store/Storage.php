<?php

Class Storage
{
    public static $path;

    public static $temporaryUrlCallbacks = array();

    public static function disk($disk)
    {
        $config = config('filesystems.disks.'.$disk);        

        if ($config['driver']=='s3') {
            $config = config('filesystems.disks.'.$disk);
            self::$path = $config['endpoint'];
            $key = $config['key'];
            $secret = $config['secret'];
            $endpoint = $config['endpoint'];
            $bucket = $config['bucket'];
            $region = $config['.region'];
            $url = $config['url'];
            return new S3Storage($key, $secret, $bucket, $region, $endpoint, $url);
        }

        else { //if ($config['driver']=='local') {
            self::$path = $config['root'];
            $path = $config['root'];
            $url = $config['url'];
            return new Filesystem($path, $url, $disk);
        }

        /* else {
            throw new LogicException("Storage driver not supported for [$disk]");
        } */

    }

    public static function json($path, $flags = 0, $lock = false)
    {
        //return file_get_contents(self::$path.$file);
        $default = config('filesystems.default');
        return self::disk($default)->json($path, $flags, $lock);
    }

    public static function get($file)
    {
        //return file_get_contents(self::$path.$file);
        $default = config('filesystems.default');
        return self::disk($default)->get($file);
    }

    public static function exists($file)
    {
        //return file_exists(self::$path.$file);
        $default = config('filesystems.default');
        return self::disk($default)->exists($file);

    }

    public static function missing($file)
    {
        return !self::exists($file);
    }

    public static function download($file, $name=null, $headers=null)
    {
        $default = config('filesystems.default');
        return self::disk($default)->download($file, $name, $headers);
    }

    public static function url($file)
    {
        $default = config('filesystems.default');
        return self::disk($default)->url($file);
    }

    public static function lastModified($file)
    {
        $default = config('filesystems.default');
        return self::disk($default)->lastModified($file);
    }

    public static function size($file)
    {
        $default = config('filesystems.default');
        return self::disk($default)->size($file);
    }

    public static function path($file)
    {
        $default = config('filesystems.default');
        return self::disk($default)->path($file);
    }

    public static function put($file, $contents)
    {
        if (is_object($contents) && get_class($contents)=='UploadedFile')
        {
            return $contents->store($file);
        }

        $default = config('filesystems.default');
        return self::disk($default)->put($file, $contents);
    }

    public static function copy($source, $dest)
    {
        $default = config('filesystems.default');
        return self::disk($default)->copy($source, $dest);
    }

    public static function move($source, $dest)
    {
        $default = config('filesystems.default');
        return self::disk($default)->move($source, $dest);
    }

    public static function putFile($path, $file=null, $options=array())
    {
        $default = config('filesystems.default');
        return self::disk($default)->putFile($path, $file, $options);
    }

    public static function putFileAs($path, $file, $name = null, $options=array())
    {
        $default = config('filesystems.default');
        return self::disk($default)->putFileAs($path, $file, $name, $options);
    }

    public static function temporaryUrl($file, $expires=60, $options=array())
    {
        $default = config('filesystems.default');
        return self::disk($default)->temporaryUrl($file, $expires, $options);
    }

    public static function delete($file)
    {
        $default = config('filesystems.default');
        $disk = self::disk($default);

        $ok = null;
        if (is_array($file))
        {
            foreach ($file as $f)
                $ok = $disk->delete($f);
        }
        else
        {
            $ok = $disk->delete($file);
        }

        return $ok;

    }


}