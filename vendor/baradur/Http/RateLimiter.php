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
        $this->cache = new FileStore(new Filesystem(), _DIR_.'storage/framework/cache', 0777);;
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

    public function attempt($key, $maxAttempts, $callback, $decaySeconds = 60)
    {
        if ($this->tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        $res = true;

        if (is_closure($callback))
        {
            list($class, $method, $params) = getCallbackFromString($callback);
            $res = executeCallback($class, $method, $params, $this);
        }

        $this->hit($key, $decaySeconds);

        return $res;
    }

    public function tooManyAttempts($key, $maxAttempts)
    {
        if ($this->attempts($key) >= $maxAttempts) {
            if ($this->cache->has($this->cleanRateLimiterKey($key).':timer')) {
                return true;
            }

            $this->resetAttempts($key);
        }

        return false;
    }

    public function hit($key, $decaySeconds = 60)
    {
        $key = $this->cleanRateLimiterKey($key);

        $this->cache->add(
            $key.':timer', $this->availableAt($decaySeconds), $decaySeconds
        );

        $added = $this->cache->add($key, 0, $decaySeconds);

        $hits = (int) $this->cache->increment($key);

        if (! $added && $hits == 1) {
            $this->cache->put($key, 1, $decaySeconds);
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

    public function remaining($key, $maxAttempts)
    {
        $key = $this->cleanRateLimiterKey($key);

        $attempts = $this->attempts($key);

        return $maxAttempts - $attempts;
    }

    public function retriesLeft($key, $maxAttempts)
    {
        return $this->remaining($key, $maxAttempts);
    }

    public function clear($key)
    {
        $key = $this->cleanRateLimiterKey($key);

        $this->resetAttempts($key);

        $this->cache->forget($key.':timer');
    }

    public function availableIn($key)
    {
        $key = $this->cleanRateLimiterKey($key);

        return max(0, $this->cache->get($key.':timer') - $this->currentTime());
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