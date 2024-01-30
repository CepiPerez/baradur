<?php

class CheckAbilities
{
    public function handle()
    {
        $abilities = func_get_args();

        $request = array_shift($abilities);
        $next = array_shift($abilities);

        if (! $request->user() || ! $request->user()->currentAccessToken()) {
            throw new AuthenticationException;
        }

        foreach ($abilities as $ability) {
            if (! $request->user()->tokenCan($ability)) {
                throw new MissingAbilityException($ability);
            }
        }

        return $request;
    }
}