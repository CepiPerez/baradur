<?php

trait HasApiTokens
{
    protected $accessToken;
    
    public function tokens()
    {
        return $this->morphMany('PersonalAccessToken', 'tokenable');
    }

    public function tokenCan($ability)
    {
        return $this->accessToken && $this->accessToken->can($ability);
    }

    public function createToken($name, $abilities = array('*'), $expiresAt = null)
    {
        $abilities = is_array($abilities) ? $abilities : array($abilities);

        $plainTextToken = sprintf(
            '%s%s%s',
            config('sanctum.token_prefix', ''),
            $tokenEntropy = Str::random(40),
            hash('crc32b', $tokenEntropy)
        );

        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }

    public function currentAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the current access token for the user.
     *
     * @param  \Laravel\Sanctum\Contracts\HasAbilities  $accessToken
     * @return $this
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}