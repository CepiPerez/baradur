<?php

class Filesystem
{

    protected $path;
    protected $url;
    protected $disk;

    public function __construct($path=null, $url=null, $disk=null)
    {
        $this->path = $path;
        $this->url = $url;
        $this->disk = $disk;
    }

    private function getPath()
    {
        if (!is_null($this->path))
            return $this->path . '/';

        return '';
    }

    private function getUrl()
    {
        if (!is_null($this->url))
            return $this->url . '/';

        return '';
    }

    public function exists($path)
    {
        $path = $this->getPath() . $path;
        return file_exists($path);
    }

    public function missing($path)
    {
        $path = $this->getPath() . $path;
        return !file_exists($path);
    }

    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        $path = $this->getPath() . $path;

        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    public function chmod($path, $mode = null)
    {
        $path = $this->getPath() . $path;

        if (!file_exists($path))
            return false;

        if ($mode) {
            return @chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    public function put($path, $contents, $lock = false)
    {
        $path = $this->getPath() . $path;

        //echo "Saving $path<br>";
        $res = file_put_contents($path, $contents, $lock ? LOCK_EX : 0);

        if ($res) @chmod($path, 0777);

        return $res===false? false : true;

    }

    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path)
        {
            $path = $this->getPath() . $path;
            try {
                if (@unlink($path)) {
                    clearstatcache(false, $path);
                } else {
                    $success = false;
                }
            } catch (ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    public function isFile($path)
    {
        //$path = $this->getPath() . $path;
        return is_file($path);
    }

    public function isDirectory($path)
    {
        //$path = $this->getPath() . $path;
        return is_dir($path);
    }

    public function type($path)
    {
        return filetype($path);
    }

    public function hash($path, $algorithm = 'md5')
    {
        return hash_file($algorithm, $path);
    }

    public function size($path)
    {
        $path = $this->getPath() . $path;

        return $this->isFile($path) ? filesize($path) : null;
    }

    public function json($path, $flags = 0, $lock = false)
    {
        return json_decode($this->get($path, $lock), true);
    }

    public function deleteDirectory($path, $preserve = false)
    {
        $path = $this->getPath() . $path;

        if (! $this->isDirectory($path)) {
            return false;
        }

        
        $files = array_diff(scandir($path), array('.','..'));
        foreach ($files as $file)
        {
            if (is_dir("$path/$file") && !is_link("$path/$file")) 
            {
                $this->deleteDirectory("$path/$file");
            }
            else
            {
                $this->delete("$path/$file");
            }
        }
        

        /* $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            dd($item);
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && ! $item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            }

            // If the item is just a file, we can go ahead and delete it since we're
            // just looping through and waxing all of the files in this directory
            // and calling directories recursively, so we delete the real path.
            else {
                $this->delete($item->getPathname());
            }
        } */

        if (! $preserve) {
            @rmdir($path);
        }

        return true;
    }


    public function directories($path)
    {
        $path = $this->getPath() . $path;

        /* $directories = [];

        foreach (Finder::create()->in($directory)->directories()->depth(0)->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        } */

        $directories = glob($path . '/*' , GLOB_ONLYDIR);
        $directories[] = $path;

        return $directories;
    }

    public function get($path, $lock = false)
    {
        $path = $this->getPath() . $path;

        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        return null;
        //throw new Exception("File does not exist at path {$path}.");
    }

    public function sharedGet($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            /* try { */
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, ($this->size($path) ? $this->size($path) : 1));

                    flock($handle, LOCK_UN);
                }
            /* } finally { */
                fclose($handle);
            /* } */
        }

        return $contents;
    }

    public function download($file, $name=null, $headers=null)
    {
        $file = $this->getPath() . $file;

        if (!isset($name)) $name = basename($file);
        $mime = mime_content_type($file);

        header('Content-type: '.$mime);
        header('Content-disposition: download; filename="'.$name.'"');
        header('content-Transfer-Encoding:binary');
        header('Accept-Ranges:bytes');

        foreach($headers as $header) {
            header($header);
        }

        @readfile($file);
        __exit();
    }

    public function url($file)
    {
        return $this->getUrl() . $file;
    }
    
    public function path($file)
    {
        return $this->getPath() . $file;
    }

    public function lastModified($path)
    {
        $path = $this->getPath() . $path;

        return $this->isFile($path) ? filemtime($path) : null;
    }

    public function copy($source, $dest)
    {
        return copy($this->getPath().$source, $this->getPath().$dest);
    }

    public function move($source, $dest)
    {
        return rename($this->getPath().$source, $this->getPath().$dest);
    }

    public function putFile($path, $file = null, $options = array())
    {
        if (is_null($file) || is_array($file)) {
            list($path, $file, $options) = array('', $path, $file ? $file : array());
        }

        $file = is_string($file) ? new File($file) : $file;

        return $this->putFileAs($path, $file, $file->hashName(), $options);
    }

    public function putFileAs($path, $file, $name = null, $options = array())
    {
        if (is_null($name) || is_array($name)) {
            list($path, $file, $name, $options) = array('', $path, $file, $name ? $name : array());
        }

        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'r');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
        $result = $this->put(
            $path = trim($path.'/'.$name, '/'), $stream, $options
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }


    public function buildTemporaryUrlsUsing($callback)
    {
        Storage::$temporaryUrlCallbacks[$this->disk] = $callback;
    }

    public function temporaryUrl($path, $expiration, $options)
    {
        if ($expiration instanceof Carbon) {
            $expiration = $expiration->timestamp; 
        }

        if (!isset(Storage::$temporaryUrlCallbacks[$this->disk]))
            throw new BadMethodCallException("Disk [$this->disk] doesn't support temporaryUrl");
      
        list($class, $method) = getCallbackFromString(Storage::$temporaryUrlCallbacks[$this->disk]);
        
        //return $class::$method($path, $expiration, $options);
        return call_user_func_array(array($class, $method), array($path, $expiration, $options));

    }

}