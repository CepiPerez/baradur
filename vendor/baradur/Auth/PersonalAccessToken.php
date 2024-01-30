<?php

class PersonalAccessToken extends Model
{
    protected $table = 'personal_access_tokens';

    protected $casts = array(
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    );

    protected $fillable = array(
        'name',
        'token',
        'abilities',
        'expires_at',
    );

    /* protected $hidden = array(
        'token',
    ); */


    public function tokenable()
    {
        return $this->morphTo('tokenable');
    }

    public static function findToken($token)
    {
        if (strpos($token, '|') === false) {
            return Model::instance('PersonalAccessToken')->where('token', hash('sha256', $token))->first();
        }

        list($id, $token) = explode('|', $token, 2);

        if ($instance = Model::instance('PersonalAccessToken')->find($id)) {
            return $instance->token==hash('sha256', $token) ? $instance : null;
        }
    }

    public function can($ability)
    {
        return in_array('*', $this->abilities) ||
               array_key_exists($ability, array_flip($this->abilities));
    }

    public function cant($ability)
    {
        return ! $this->can($ability);
    }
}