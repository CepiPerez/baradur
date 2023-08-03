<?php

class ThrottleRequests
{
    protected $limiter;

    public function __construct()
    {
        $this->limiter = RateLimiter::getInstance();
    }

    public function handle($request, $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        //dump(func_get_args());__exit();
        if (is_numeric($maxAttempts)) {
            $maxAttempts = intval($maxAttempts);
        }
        
        if (is_string($maxAttempts)
            && func_num_args() === 3
            && ! is_null($limiter = $this->limiter->limiter($maxAttempts))) {
            return $this->handleRequestUsingNamedLimiter($request, $next, $maxAttempts, $limiter);
        }

        $limits = array(
            'key' => $prefix.$this->resolveRequestSignature($request),
            'maxAttempts' => $this->resolveMaxAttempts($request, $maxAttempts),
            'decayMinutes' => $decayMinutes,
            'responseCallback' => null
        );

        return $this->handleRequest(
            $request,
            $next,
            (object)$limits
        );
    }

    protected function handleRequestUsingNamedLimiter($request, $next, $limiterName, $limiter)
    {
        $limiterResponse = null;

        if (is_closure($limiter)) {
            list($class, $method) = getCallbackFromString($limiter);
            $limiterResponse = executeCallback($class, $method, array($request));
        }

        if ($limiterResponse instanceof Response) {
            return $limiterResponse;
        } elseif ($limiterResponse instanceof Unlimited) {
            return $request;
        }

        $limits = array(
            'key' => md5($limiterName.$limiterResponse->key),
            'maxAttempts' => $limiterResponse->maxAttempts,
            'decayMinutes' => $limiterResponse->decayMinutes,
            'responseCallback' => $limiterResponse->responseCallback
        );

        return $this->handleRequest(
            $request,
            $next,
            (object)$limits
        );
    }

    protected function handleRequest($request, $next, $limits)
    {
        $limits = is_array($limits) ? $limits : array($limits);
        
        foreach ($limits as $limit)
        {
            if ($this->limiter->tooManyAttempts($limit->key, $limit->maxAttempts)) {
                throw $this->buildException($request, $limit->key, $limit->maxAttempts, $limit->responseCallback);
            }

            $this->limiter->hit($limit->key, $limit->decayMinutes * 60);
        }

        $response = $request;

        foreach ($limits as $limit) {
            $response = $this->addHeaders(
                $response,
                $limit->maxAttempts,
                $this->calculateRemainingAttempts($limit->key, $limit->maxAttempts)
            );
        }

        return $response;
    }

    protected function resolveMaxAttempts($request, $maxAttempts)
    {
        if (str_contains($maxAttempts, '|')) {
            $maxAttempts = explode('|', $maxAttempts, 2);
            $maxAttempts = $maxAttempts[$request->user() ? 1 : 0];
        }

        if (! is_numeric($maxAttempts) && $request->user()) {
            $maxAttempts = $request->user()->{$maxAttempts}
                ? $request->user()->{$maxAttempts}
                : 60;
        }

        return (int) $maxAttempts;
    }

    protected function resolveRequestSignature($request)
    {
        if ($user = $request->user()) {
            return sha1($user->id);
        } elseif ($route = $request->route()) {
            return sha1($route->url.'|'.$request->ip());
        }

        throw new RuntimeException('Unable to generate the request signature. Route unavailable.');
    }

    protected function buildException($request, $key, $maxAttempts, $responseCallback = null)
    {
        $retryAfter = $this->getTimeUntilNextRetry($key);

        $headers = $this->getHeaders(
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts, $retryAfter),
            $retryAfter
        );

        return is_callable($responseCallback)
            ? new HttpResponseException($responseCallback($request, $headers))
            : new ThrottleRequestsException('Too Many Attempts.', null, $headers);

    }

    protected function getTimeUntilNextRetry($key)
    {
        return $this->limiter->availableIn($key);
    }

    protected function addHeaders($response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $response->addHeaders(
            $this->getHeaders($maxAttempts, $remainingAttempts, $retryAfter, $response)
        );

        return $response;
    }

    protected function getHeaders($maxAttempts, $remainingAttempts, $retryAfter = null, $response = null)
    {
        if ($response &&
            ! is_null($response->header('X-RateLimit-Remaining')) &&
            (int) $response->header('X-RateLimit-Remaining') <= (int) $remainingAttempts) {
            return array();
        }

        $headers = array(
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        );

        if (! is_null($retryAfter)) {
            $headers['Retry-After'] = $retryAfter;
            $headers['X-RateLimit-Reset'] = $this->availableAt($retryAfter);
        }

        return $headers;
    }

    protected function calculateRemainingAttempts($key, $maxAttempts, $retryAfter = null)
    {
        return is_null($retryAfter) 
            ? $this->limiter->retriesLeft($key, $maxAttempts) 
            : 0;
    }

    protected function secondsUntil($delay)
    {
        $delay = $this->parseDateInterval($delay);

        return $delay instanceof Carbon
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