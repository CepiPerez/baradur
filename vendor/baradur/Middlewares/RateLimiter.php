<?php

/**
 * @method static RateLimiter for(string $key, callable $callback)
*/

class RateLimiter
{
    protected $cache;
    protected $limiters = array();

    protected static $instance = null;

    public function __construct()
    {
        $this->cache = new FileStore(new Filesystem(), _DIR_.'storage/framework/cache', 0777);
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new RateLimiter;
        }

        return self::$instance;
    }
    
    public static function instanceFor($name, $callback)
    {
        $instance = self::getInstance();

        $instance->limiters[$name] = $callback;

        return $instance;
    }

    public function limiter($name)
    {
        return $this->limiters[$name] ? $this->limiters[$name] : null;
    }

    public static function attempt($key, $maxAttempts, $callback, $decaySeconds = 60)
    {
        $instance = self::getInstance();

        if ($instance->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        if (is_closure($callback)) {
            list($class, $method, $params) = getCallbackFromString($callback);
            $res = executeCallback($class, $method, $params, $instance);
        }

        $instance->hit($key, $decaySeconds);

        return $res? $res : true;
    }

    public static function tooManyAttempts($key, $maxAttempts)
    {
        $instance = self::getInstance();

        if ($instance->attempts($key) >= $maxAttempts) {
            if ($instance->cache->has($instance->cleanRateLimiterKey($key).':timer')) {
                return true;
            }

            $instance->resetAttempts($key);
        }

        return false;
    }

    public static function hit($key, $decaySeconds = 60)
    {
        $instance = self::getInstance();

        $key = $instance->cleanRateLimiterKey($key);

        //dd("HOLA", $key.':timer', $instance->availableAt($decaySeconds), $decaySeconds);
        $instance->cache->add(
            $key.':timer', $instance->availableAt($decaySeconds), $decaySeconds
        );

        $added = $instance->cache->add($key, 0, $decaySeconds);

        $hits = (int) $instance->cache->increment($key);

        if (! $added && $hits == 1) {
            $instance->cache->put($key, 1, $decaySeconds);
        }

        return $hits;
    }

    public function attempts($key)
    {
        $key = $this->cleanRateLimiterKey($key);

        return $this->cache->get($key);
    }

    public function resetAttempts($key)
    {
        $key = $this->cleanRateLimiterKey($key);

        return $this->cache->forget($key);
    }

    public static function remaining($key, $maxAttempts)
    {
        $instance = self::getInstance();

        $key = $instance->cleanRateLimiterKey($key);

        $attempts = $instance->attempts($key);

        return $maxAttempts - $attempts;
    }

    public function retriesLeft($key, $maxAttempts)
    {
        return $this->remaining($key, $maxAttempts);
    }

    public static function clear($key)
    {
        $instance = self::getInstance();

        $key = $instance->cleanRateLimiterKey($key);

        $instance->resetAttempts($key);

        $instance->cache->forget($key.':timer');
    }

    public static function availableIn($key)
    {
        $instance = self::getInstance();

        $key = $instance->cleanRateLimiterKey($key);

        return max(0, $instance->cache->get($key.':timer') - $instance->currentTime());
    }

    public function cleanRateLimiterKey($key)
    {
        return preg_replace('/&([a-z])[a-z]+;/i', '$1', htmlentities($key));
    }

    protected function secondsUntil($delay)
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof DateTimeInterface
            ? max(0, $delay->getTimestamp() - $this->currentTime())
            : (int) $delay;
    }

    protected function availableAt($delay = 0)
    {
        $delay = $this->parseDateInterval($delay);

        return ($delay instanceof Carbon)
            ? $delay->getTimestamp()
            : Carbon::now()->addSeconds($delay)->getTimestamp();
    }

    protected function parseDateInterval($delay)
    {
        if (!($delay instanceof Carbon)) {
            $delay = Carbon::now()->addSeconds($delay);
        }

        return $delay;
    }

    protected function currentTime()
    {
        return Carbon::now()->getTimestamp();
    }
}