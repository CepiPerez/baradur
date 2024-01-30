<?php

class Attachment
{
    public $as;
    public $mime;
    public $path;
    public $disk;

    private $data;

    /**
     * Create a mail attachment from a path.
     *
     * @param  string  $path
     * @return Attachment
     */
    public static function fromPath($path)
    {
        $instance = new Attachment;
        $instance->path = $path;

        return $instance;
    }

    /**
     * Create a mail attachment from in-memory data.
     *
     * @param  $data
     * @param  string|null  $name
     * @return Attachment
     */
    public static function fromData($data, $name = null)
    {
        if (!is_closure($data)) {
            throw new LogicException("Error reading data callback.");
        }

        list($class, $method) = getCallbackFromString($data);

        $instance = new Attachment;
        $instance->data = executeCallback($class, $method, array());
        $instance->as = $name;

        return $instance;
    }

    /**
     * Create a mail attachment from a file in the default storage disk.
     *
     * @param  string  $path
     * @return Attachment
     */
    public static function fromStorage($path)
    {
        return self::fromStorageDisk(null, $path);
    }

    /**
     * Create a mail attachment from a file in the specified storage disk.
     *
     * @param  string|null  $disk
     * @param  string  $path
     * @return Attachment
     */
    public static function fromStorageDisk($disk, $path)
    {
        /* return new static(function ($attachment, $pathStrategy, $dataStrategy) use ($disk, $path) {
            $storage = Container::getInstance()->make(
                FilesystemFactory::class
            )->disk($disk);

            $attachment
                ->as($attachment->as ?? basename($path))
                ->withMime($attachment->mime ?? $storage->mimeType($path));

            return $dataStrategy(fn () => $storage->get($path), $attachment);
        }); */

        //$new_path = $disk ? Storage::disk($disk)->path($path) : Storage::path($path);

        /* $parsed = parse_url($path);

        $url = $parsed['scheme'] . '://' . $parsed['host'];
        if ($parsed['port']) $url .= ':' . $parsed['port'];
        $url .= $parsed['path']; */

        $instance = new Attachment;
        $instance->disk = $disk;
        $instance->path = $path;

        return $instance;
    }

    /**
     * Set the attached file's filename.
     *
     * @param  string|null  $name
     * @return $this
     */
    public function __as($name)
    {
        $this->as = $name;

        return $this;
    }

    /**
     * Set the attached file's mime type.
     *
     * @param  string  $mime
     * @return $this
     */
    public function withMime($mime)
    {
        $this->mime = $mime;

        return $this;
    }

}