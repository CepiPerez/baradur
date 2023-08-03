<?php

class SessionGuard
{
    public $name;
    protected $lastAttempted;
    protected $viaRemember = false;
    protected $rememberDuration = 576000;
    protected $session;
    protected $cookie;
    protected $request;
    protected $events;
    protected $timebox;
    protected $loggedOut = false;
    protected $recallAttempted = false;

    protected $user;
    protected $provider;

    public function __construct($name, $provider, $session, $request = null, $timebox = null)
    {
        $this->name = $name;
        $this->session = $session;
        $this->request = $request;
        $this->provider = $provider;
        $this->timebox = $timebox;
    }

    public function attempt($credentials, $remember = false)
    {
        /* $user = Model::instance($this->provider)
            ->where('username', $credentials['email'])
            ->orWhere('email', $credentials['email'])
            ->first();

        if (!$user || strcmp($user->password, md5($credentials['password']))) {
            return false;
        } */

        $user = $this->provider->retrieveByCredentials($credentials);

        if (! $this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        /* if (isset($this->user->token)) {
            return false;
        } */

        $this->login($user, $remember);

        return true;
    }

    public function login($user, $remember = false)
    {
        $token = md5($user->username.'_'.$user->password.'_'.strtotime('now'));

        $token_key = $user->getRememberTokenName();

        $user->$token_key = $token;

        $user->save();

        if ($remember) {
            $domain = $_SERVER["HTTP_HOST"];
            setcookie(config('app.name').'_token', $token, time()+$this->rememberDuration, '/', $domain, false, true);
        }

        $user->unsetAttribute('password');

        $this->user = $user;

        session()->regenerate(false);

        $_SESSION['guard'] = $this; 
        //$_SESSION['user'] = $user;
        
        return true;
    } 

    public function logout()
    {
        //$user = Model::instance($this->provider)->find($this->id());
        $user = $this->provider->retrieveById($this->id());
        $user->token = null;
        $user->save();

        $domain = $_SERVER["HTTP_HOST"];
        setcookie(config('app.name').'_token', '', time() - 3600, '/', $domain);

        $this->user = null;
        $this->loggedOut = true;

        session()->regenerate(true);
    }

    public function loginUsingId($id, $remember = true)
    {
        //$user = Model::instance($this->provider)->findOrFail($id);
        $user = $this->provider->retrieveById($id, $remember);

        $this->login($user, $remember);
    }

    /** @return Authenticatable */
    public function user()
    {
        return $this->user;
    }

    public function authenticate()
    {
        if (! is_null($user = $this->user())) {
            return $user;
        }

        throw new AuthenticationException;
    }

    public function hasUser()
    {
        return ! is_null($this->user);
    }

    public function check()
    {
        return ! is_null($this->user());
    }

    public function guest()
    {
        return ! $this->check();
    }

    public function forgetUser()
    {
        $this->user = null;

        return $this;
    }

    public function getProvider()
    {
        return $this->provider;
    }

    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    public function id()
    {
        return $this->user
            ? $this->user()->getAuthIdentifier()
            : null; //$this->session->get($this->getName());
    }


}
