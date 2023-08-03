<?php

/**
 * @method static Sleep for($duration)
*/

class Sleep
{
    public $duration;

    protected $pending = null;
    protected $shouldSleep = true;

    /**
     * Create a new class instance.
     *
     * @param $duration
     * @return void
     */
    public function __construct($duration)
    {
        $this->duration($duration);
    }

    /**
     * Sleep for the given duration.
     *
     * @param $duration
     * @return Sleep
     */
    public static function instanceFor($duration)
    {
        return new self($duration);
    }

    /**
     * Sleep until the given timestamp.
     *
     * @param $timestamp
     * @return static
     */
    public static function until($timestamp)
    {
        if ($timestamp instanceof Carbon) {
            $timestamp = $timestamp->getTimestamp();
        }

        if (!is_int($timestamp)) {
            throw new Exception('Error! Given timestamp must be integer or Carbon instance.');
        }

        $now = Carbon::now()->getTimestamp();
        $diff = abs($timestamp - $now);
        $instance = new self($diff);

        return $instance->seconds();
    }

    /**
     * Sleep for the given number of microseconds.
     *
     * @param $duration
     * @return static
     */
    public static function usleep($duration)
    {
        $instance = new self($duration);
        return $instance->microseconds();
    }

    /**
     * Sleep for the given number of seconds.
     *
     * @param $duration
     * @return static
     */
    public static function sleep($duration)
    {
        $instance = new self($duration);
        return $instance->seconds();
    }

    /**
     * Sleep for the given duration. Replaces any previously defined duration.
     *
     * @param $duration
     * @return $this
     */
    protected function duration($duration)
    {
        $this->duration = $duration;
        $this->pending = 0;

        return $this;
    }

    /**
     * Sleep for the given number of minutes.
     *
     * @return $this
     */
    public function minutes()
    {
        $this->pending += ($this->duration * 1000) * 60000;
        $this->duration = 0;

        return $this;
    }

    /**
     * Sleep for one minute.
     *
     * @return $this
     */
    public function minute()
    {
        return $this->minutes();
    }

    /**
     * Sleep for the given number of seconds.
     *
     * @return $this
     */
    public function seconds()
    {
        $this->pending += ($this->duration * 1000) * 1000;
        $this->duration = 0;

        return $this;
    }

    /**
     * Sleep for one second.
     *
     * @return $this
     */
    public function second()
    {
        return $this->seconds();
    }

    /**
     * Sleep for the given number of milliseconds.
     *
     * @return $this
     */
    public function milliseconds()
    {
        $this->pending += $this->duration * 1000;
        $this->duration = 0;

        return $this;
    }

    /**
     * Sleep for one millisecond.
     *
     * @return $this
     */
    public function millisecond()
    {
        return $this->milliseconds();
    }

    /**
     * Sleep for the given number of microseconds.
     *
     * @return $this
     */
    public function microseconds()
    {
        $this->pending += $this->duration;
        $this->duration = 0;

        return $this;
    }

    /**
     * Sleep for on microsecond.
     *
     * @return $this
     */
    public function microsecond()
    {
        return $this->microseconds();
    }

    /**
     * Add additional time to sleep for.
     *
     * @param  int|float  $duration
     * @return $this
     */
    public function andFor($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Handle the object's destruction.
     *
     * @return void
     */
    public function __destruct()
    {
        /* if (! $this->shouldSleep) {
            return;
        }

        if ($this->pending !== null) {
            throw new RuntimeException('Unknown duration unit.');
        }

        if (static::$fake) {
            static::$sequence[] = $this->duration;

            foreach (static::$fakeSleepCallbacks as $callback) {
                $callback($this->duration);
            }

            return;
        }

        $remaining = $this->duration->copy();

        $seconds = (int) $remaining->totalSeconds;

        if ($seconds > 0) {
            sleep($seconds);

            $remaining = $remaining->subSeconds($seconds);
        }

        $microseconds = (int) $remaining->totalMicroseconds;

        if ($microseconds > 0) {
            usleep($microseconds);
        } */

        //dump("slepp for", $this->pending, $this);

        usleep($this->pending);
    }

    /**
     * Resolve the pending duration.
     *
     * @return int|float
     */
    /* protected function pullPending()
    {
        if ($this->pending === null) {
            $this->shouldNotSleep();

            throw new RuntimeException('No duration specified.');
        }

        if ($this->pending < 0) {
            $this->pending = 0;
        }

        return tap($this->pending, function () {
            $this->pending = null;
        });
    } */

    
    /**
     * Indicate that the instance should not sleep.
     *
     * @return $this
     */
    protected function shouldNotSleep()
    {
        $this->shouldSleep = false;

        return $this;
    }

    /**
     * Only sleep when the given condition is true.
     *
     * @param  (\Closure($this): bool)|bool  $condition
     * @return $this
     */
    /* public function when($condition)
    {
        $this->shouldSleep = (bool) value($condition, $this);

        return $this;
    } */

    /**
     * Don't sleep when the given condition is true.
     *
     * @param  (\Closure($this): bool)|bool  $condition
     * @return $this
     */
    /* public function unless($condition)
    {
        return $this->when(! value($condition, $this));
    } */

}