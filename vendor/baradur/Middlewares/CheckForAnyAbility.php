<?php

class CheckForAnyAbility
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
            if ($request->user()->tokenCan($ability)) {
                return $request;
            }
        }

        throw new MissingAbilityException($abilities);
    }
}