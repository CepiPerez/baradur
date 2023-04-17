<?php

class URL
{

    public static function temporarySignedRoute($name, $expiration, $parameters = array(), $absolute = true)
    {
        return self::signedRoute($name, $parameters, $expiration, $absolute);
    }

    public static function signedRoute($name, $parameters = array(), $expiration = null, $absolute = true)
    {
        /* $this->ensureSignedRouteParametersAreNotReserved(
            $parameters = Arr::wrap($parameters)
        ); */

        if ($expiration) {
            if ($expiration instanceof Carbon) {
                $expiration = $expiration->timestamp; 
            }
        
            $parameters = array_merge($parameters, array('expires' => $expiration));
        }

        ksort($parameters);

        $key = session_id();

        return self::route($name, $parameters + array(
            'signature' => hash_hmac('sha256', self::route($name, $parameters, $absolute), $key),
        ), $absolute);
    }

    public static function route($name, $parameters = array(), $absolute = true)
    {
        if (! is_null($route = route($name))) {            
            return $route . (count($parameters)>0 ? '?'.http_build_query($parameters) : '');
        }

        throw new RouteNotFoundException("Route [{$name}] not defined.");
    }

    public static function hasValidSignature($request, $absolute = true, $ignoreQuery = array())
    {
        return self::hasCorrectSignature($request, $absolute, $ignoreQuery)
            && self::signatureHasNotExpired($request);
    }

    public static function hasCorrectSignature($request, $absolute = true, $ignoreQuery = array())
    {
        $ignoreQuery[] = 'signature';

        $url = $absolute ? $request->url() : '/'.$request->path();

        $queryString = $request->query();

        foreach ($ignoreQuery as $ignore) {
            unset($queryString[$ignore]);
        }

        $queryString = http_build_query($queryString);

        $original = rtrim($url.'?'.$queryString, '?');

        $signature = hash_hmac('sha256', $original, session_id()); //call_user_func($this->keyResolver));

        return hash_equals($signature, (string) $request->query('signature', ''));
    }

    public static function signatureHasNotExpired($request)
    {
        $expires = $request->query('expires');

        return ! ($expires && Carbon::now()->getTimestamp() > $expires);
    }


}