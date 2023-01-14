<?php

Class Storage
{
    public static $path;

    public static function disk($disk)
    {
        $config = config('filesystems.disks.'.$disk);

        
        if ($config['driver']=='local') {
            self::$path = $config['root'];
            $path = $config['root'];
            $url = $config['url'];
            return new Filesystem($path, $url);
        }

        elseif ($config['driver']=='s3') {
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

        else {
            throw new Exception("Storage driver not supported for [$disk]");
        }

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
        //return !file_exists(self::$path.$file);
        return !self::exists($file);
    }

    public static function download($file, $name=null, $headers=null)
    {
        /* if (!isset($name)) $name = basename($file);
        $mime = mime_content_type(self::$path.$file);

        header('Content-type: '.$mime);
        header('Content-disposition: download; filename="'.$name.'"');
        header('content-Transfer-Encoding:binary');
        header('Accept-Ranges:bytes');
        @readfile(self::$path.$file);
        exit(); */
        $default = config('filesystems.default');
        return self::disk($default)->download($file);
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
        if (is_object($contents) && get_class($contents)=='StorageFile')
        {
            return $contents->store($file);
        }

        /* $res = file_put_contents(self::$path.$file, $contents);

        if ($res) @chmod(self::$path.$file, 0777);

        return $res; */
        $default = config('filesystems.default');
        return self::disk($default)->put($file, $contents);
    }

    public static function copy($source, $dest)
    {
        //return copy(self::$path.$source, self::$path.$dest);
        $default = config('filesystems.default');
        return self::disk($default)->copy($source, $dest);
    }

    public static function move($source, $dest)
    {
        //return rename(self::$path.$source, self::$path.$dest);
        $default = config('filesystems.default');
        return self::disk($default)->move($source, $dest);
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