<?php

class Filesystem
{

    public function exists($path)
    {
        return file_exists($path);
    }

    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    public function chmod($path, $mode = null)
    {
        if (!file_exists($path))
            return false;

        if ($mode) {
            return @chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    public function put($path, $contents, $lock = false)
    {
        //echo "Saving $path<br>";
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    public function delete($paths)
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
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

    public function isFile($file)
    {
        return is_file($file);
    }

    public function isDirectory($directory)
    {
        return is_dir($directory);
    }

    public function size($path)
    {
        return filesize($path);
    }

    public function deleteDirectory($directory, $preserve = false)
    {
        if (! $this->isDirectory($directory)) {
            return false;
        }

        
        $files = array_diff(scandir($directory), array('.','..'));
        foreach ($files as $file)
        {
            if (is_dir("$directory/$file") && !is_link("$directory/$file")) 
            {
                $this->deleteDirectory("$directory/$file");
            }
            else
            {
                $this->delete("$directory/$file");
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
            @rmdir($directory);
        }

        return true;
    }


    public function directories($directory)
    {
        /* $directories = [];

        foreach (Finder::create()->in($directory)->directories()->depth(0)->sortByName() as $dir) {
            $directories[] = $dir->getPathname();
        } */

        $directories = glob($directory . '/*' , GLOB_ONLYDIR);
        $directories[] = $directory;

        return $directories;
    }

    public function get($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->sharedGet($path) : file_get_contents($path);
        }

        throw new Exception("File does not exist at path {$path}.");
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
    

}