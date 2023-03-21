<?php

class Lottery 
{
    protected $chances;
    protected $outOf;
    protected $winner;
    protected $loser;

    protected static $resultFactory = array();

    protected static $always = null;

    /**
     * Create a new Lottery instance.
     *
     * @param  int|float  $chances
     * @param  int|null  $outOf
     * @return void
     */
    public function __construct($chances, $outOf = null)
    {
        if ($outOf === null && is_float($chances) && $chances > 1) {
            throw new RuntimeException('Float must not be greater than 1.');
        }

        $this->chances = $chances;

        $this->outOf = $outOf;
    }

    /**
     * Create a new Lottery instance.
     *
     * @param  int|float  $chances
     * @param  int|null  $outOf
     * @return Lottery
     */
    public static function odds($chances, $outOf = null)
    {
        return new Lottery($chances, $outOf);
    }

    /**
     * Set the winner callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function winner($callback)
    {
        $this->winner = $callback;

        return $this;
    }

    /**
     * Set the loser callback.
     *
     * @param  callable  $callback
     * @return $this
     */
    public function loser($callback)
    {
        $this->loser = $callback;

        return $this;
    }

    /**
     * Run the lottery.
     *
     * @param  mixed  ...$args
     * @return mixed
     */
    public function __invoke()
    {
        return $this->runCallback(/* func_get_args() */);
    }

    /**
     * Run the lottery.
     *
     * @param  null|int  $times
     * @return mixed
     */
    public function choose($times = null)
    {
        if ($times === null) {
            return $this->runCallback();
        }

        $results = array();

        for ($i = 0; $i < $times; $i++) {
            $results[] = $this->runCallback();
        }

        return $results;
    }

    protected function executeCallback($callback, $default)
    {
        if (!is_closure($callback)) {
            return $default;
        }

        list($class, $method) = getCallbackFromString($callback);

        return executeCallback($class, $method, $default);
    }

    /**
     * Run the winner or loser callback, randomly.
     *
     * @param  mixed  ...$args
     * @return callable
     */
    protected function runCallback()
    {
        //$args = func_get_args();
        
        return $this->wins()
            ? $this->executeCallback($this->winner, true)
            : $this->executeCallback($this->loser, false);
    }

    /**
     * Determine if the lottery "wins" or "loses".
     *
     * @return bool
     */
    protected function wins()
    {
        return self::resultFactory($this->chances, $this->outOf);
        //return $factory($this->chances, $this->outOf);
    }

    /**
     * The factory that determines the lottery result.
     *
     * @return callable
     */
    protected static function resultFactory($chances, $outOf)
    {
        if (self::$always=='win') {
            return true;
        }

        if (self::$always=='lose') {
            return false;
        }

        if (count(self::$resultFactory)>0) {
            return array_shift(self::$resultFactory);
        }

        return $outOf === null
            ? random_int(0, PHP_INT_MAX) / PHP_INT_MAX <= $chances
            : random_int(1, $outOf) <= $chances;
    }

    /**
     * Force the lottery to always result in a win.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public static function alwaysWin($callback = null)
    {
        self::$always = 'win';
    }

    /**
     * Force the lottery to always result in a lose.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public static function alwaysLose($callback = null)
    {
        /* self::setResultFactory(fn () => false);

        if ($callback === null) {
            return;
        }

        $callback();

        self::determineResultNormally(); */
        self::$always = 'lose';
    }

    /**
     * Set the sequence that will be used to determine lottery results.
     *
     * @param  array  $sequence
     * @param  callable|null  $whenMissing
     * @return void
     */
    public static function fix($sequence/* , $whenMissing = null */)
    {
        //return static::forceResultWithSequence($sequence, $whenMissing);

        self::$always = null;
        self::$resultFactory = $sequence;

    }

    /**
     * Set the sequence that will be used to determine lottery results.
     *
     * @param  array  $sequence
     * @param  callable|null  $whenMissing
     * @return void
     */
    /* public static function forceResultWithSequence($sequence, $whenMissing = null)
    {
        $next = 0;

        $whenMissing ??= function ($chances, $outOf) use (&$next) {
            $factoryCache = static::$resultFactory;

            static::$resultFactory = null;

            $result = static::resultFactory()($chances, $outOf);

            static::$resultFactory = $factoryCache;

            $next++;

            return $result;
        };

        static::setResultFactory(function ($chances, $outOf) use (&$next, $sequence, $whenMissing) {
            if (array_key_exists($next, $sequence)) {
                return $sequence[$next++];
            }

            return $whenMissing($chances, $outOf);
        });
    } */

    /**
     * Indicate that the lottery results should be determined normally.
     *
     * @return void
     */
    public static function determineResultNormally()
    {
        self::$resultFactory = array();
        self::$always = null;
    }

    /**
     * Set the factory that should be used to determine the lottery results.
     *
     * @param  callable  $factory
     * @return void
     */
    /* public static function setResultFactory($factory)
    {
        self::$resultFactory = $factory;
    } */
}