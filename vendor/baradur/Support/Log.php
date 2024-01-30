<?php

Class Log
{

    /**
     * Log an emergency message to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function emergency($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an alert message to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function alert($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a critical message to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function critical($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an error message to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function error($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a warning message to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function warning($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a notice to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function notice($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log an informational message to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function info($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a debug message to the logs.
     *
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function debug($message, $context = array())
    {
        self::writeLog(__FUNCTION__, $message, $context);
    }

    /**
     * Log a message to the logs.
     *
     * @param  $level
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function log($level, $message, $context = array())
    {
        self::writeLog($level, $message, $context);
    }

    /**
     * Dynamically pass log calls into the writer.
     *
     * @param  $level
     * @param  $message
     * @param  $context
     * @return void
     */
    public static function write($level, $message, $context = array())
    {
        self::writeLog($level, $message, $context);
    }

    /**
     * Write a message to the log.
     *
     * @param  $level
     * @param  $message
     * @param  $context
     * @return void
     */
    protected static function writeLog($level, $message, $context)
    {
        $message = self::formatMessage($message);

        file_put_contents(
            _DIR_ . '/storage/logs/baradur.log', 
            $level . ": " . $message . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

    }

    

    /**
     * Format the parameters for the logger.
     *
     * @param  $message
     * @return string
     */
    protected static function formatMessage($message)
    {
        if (is_array($message)) {
            return var_export($message, true);
        } /* elseif ($message instanceof Jsonable) {
            return $message->toJson();
        } elseif ($message instanceof Arrayable) {
            return var_export($message->toArray(), true);
        } */

        return (string) $message;
    }

}