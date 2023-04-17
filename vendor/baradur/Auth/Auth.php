<?php

class Auth extends Controller
{
    protected $guard = null;
    protected $model = null;

    //protected $primaryKey = 'username';
    //protected static $useAuth = false;
    //protected static $_currentUser = null;

    public static $guards = array();

    /** @return SessionGuard */
    private static function getGuard($guard=null)
    {
        if (isset($_SESSION['guard'])) {

            $stored = $_SESSION['guard'];
            self::$guards[$stored->name] = $stored;

            if ($stored->name==$guard || !$guard) {
                return $stored;
            }
        }

        if (!$guard) {
            $guard = config('auth.defaults.guard');
        }

        if (isset(self::$guards[$guard])) {
            return self::$guards[$guard];
        }

        $config = config('auth.guards.'.$guard);

        if ($config===null) {
            throw new RuntimeException("Guard [$guard] not defined.");
        }

        $provider = config('auth.providers.'.$config['provider']);

        if ($provider===null || !is_array($provider) || !isset($provider['model'])) {
            throw new RuntimeException("Provider [" . $config['provider'] . "] not defined correctly.");
        }

        $model = $provider['model'];

        $provider = ucfirst($provider['driver']) . $model . 'Provider';

        global $_class_list;
        if (!isset($_class_list[$provider])) {
            $provider = 'EloquentUserProvider';
        }

        self::$guards[$guard] = new SessionGuard($guard, new $provider($model), new RequestSession());

        return self::$guards[$guard];
    }

    /** @return SessionGuard */
    public static function guard($guard=null)
    {
        return self::getGuard($guard);
    }

    public static function user()
    {
        return self::getGuard()->user();
    }

    public static function check()
    {
        return self::getGuard()->check();
    }

    public static function id()
    {
        return self::getGuard()->id();
    }

    public static function loginUsingId($id, $remember = true)
    {
        self::getGuard()->logout();
        return self::getGuard()->loginUsingId($id, $remember);
    }


    public static function api_login($username, $password)
    {
        $user = Model::instance('User')->where('email', $username)
                    ->orWhere('username', $username)->first();

        if (!$user || strcmp($user->password, md5($password))) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(array("error"=>"Access denied. Bad credentials"));
            __exit();
        }

        if (!$user->token) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(array("error"=>"Access denied. User validation required!"));
            __exit();
        }

        $token = md5($user->username.'_'.$user->password.'_'.Carbon::now()->getTimestamp());
        $user->token = $token;
        $user->token_timestamp = Carbon::now()->getTimestamp();
        $user->save();

        return $token;

    }


    public function start_login($referer = null)
    {
        if (isset($_SESSION['url_history'][1]) && isset($_SESSION['_requestedRoute']) && $_SESSION['url_history'][1] != $_SESSION['_requestedRoute']) {
            $_SESSION['_requestedRoute'] = $_SESSION['url_history'][1];
        }

        $title = __('login.login');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.login') => '#'
        );

        return view('auth/login', compact('title', 'breadcrumb'));
    }

    public function confirm_login(Request $request)
    {
        $guard = self::getGuard(config('auth.defaults.guard'));
        
        $result = $guard->attempt(
            array('username' => $request->username, 'password' => $request->password),
            $request->remember
        );

        if (!$result) {
            return back()->with("error", __("login.invalid"));
        }

        //dd($result, self::$guards, self::getGuard());

        /* $user = Model::instance($this->model)->where('email', $request->username)
                    ->orWhere('username', $request->username)->first();

        if (strcmp($user->password, md5($request->password)) || !$user) {
            return back()->with("error", __("login.invalid"));
        }

        if (isset($user->token)) {
            return back()->with("error", __("login.validation_required"));
        }

        self::login($user, $request->remember); */

        //return redirect(config('app.url'));

        if (isset($_SESSION['_requestedRoute'])) {
            $res = $_SESSION['_requestedRoute'];
            unset($_SESSION['_requestedRoute']);
            //$res = str_replace(config('app.url'), '', $res);
            return redirect($res);
        }

        return redirect(config('app.url'));
    }

    public function logout()
    {
        $guard = self::getGuard();

        $guard->logout();
        /* $user = Model::instance($this->model)->find(auth()->id());
        $user->token = null;
        $user->save();

        $domain = $_SERVER["HTTP_HOST"];
        setcookie(config('app.name').'_token', '', time() - 3600, '/', $domain);

        unset($_SESSION['user']);
        unset($_SESSION['tokens']);
        self::$_currentUser = null; */

        return redirect(config('app.url'));
    }

    public function register()
    {
        //unset($_SESSION['user']);
        unset($_SESSION['tokens']);
        //self::$_currentUser = null;

        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        return view('auth/register', compact('title', 'breadcrumb'));
    }

    public function send_register(Request $request)
    {
        $guard = self::getGuard(config('auth.defaults.guard'));

        $request->validate(array(
            'username' => 'required|unique,users',
            'email' => 'required|unique,email',
            'password' => 'required',
            'name' => 'required'
        ));

        $title = __('login.message_sent');

        $random = substr(md5(rand()), 0, 20);

        $message = __('login.content_registration')."\n".
            __('login.follow_finish')."\n\n"
            .rtrim(config('app.url'), '/') . "/email_confirm" . "/".
            $request->email . "/" . $random . "\n\n".__('login.thanks'); 
    
        Mail::to($request->email)
			->subject(__('login.register_confirmation'))
			->plain($message);

        $model = $guard->getProvider()->getModel();
        $user = new $model;
        $user->username = $request->username;
        $user->password = md5($request->password);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->validation = $random;
        $user->save();

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $reg_message = __('login.message_sent').'<br><br>'.
            __('login.follow_registration').'<br><br>'.
            __('login.thanks').'<br>';

        return view('auth/message', compact('title', 'breadcrumb', 'reg_message'));
    }

    public function confirm($email, $token)
    {
        $guard = self::getGuard(config('auth.defaults.guard'));

        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $guard = self::getGuard(config('auth.defaults.guard'));

        $user = Model::instance($guard->getProvider()->getModel())
            ->where('email', $email)
            ->where('validation', $token)
            ->first();

        if (!$user) {
            abort(403);
        }

        $token = md5($user->username.'_'.$user->password.'_'.Carbon::now()->getTimestamp());

        $user->validation = null;
        $user->token = $token;
        $user->token_timestamp = Carbon::now()->getTimestamp();
        $user->save();

        return view('auth/completed', compact('title', 'breadcrumb'));
    }

    public function verify()
    {
        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.register_confirmation') => '#'
        );

        return view('auth/verify', compact('title', 'breadcrumb'));
    }

    public function reset()
    {
        $title = __('login.restore');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        return view('auth/reset', compact('title', 'breadcrumb'));
    }

    public function restore(Request $request)
    {
        $guard = self::getGuard(config('auth.defaults.guard'));

        $request->validate(array(
            'username' => 'required'
        ));

        $title = __('login.message_sent');
        
        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = Model::instance($guard->getProvider()->getModel())
            ->where('username', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        if (!$user)
        {
            return back()->with("error", __('login.no_user'));
        }

        $random = substr(md5(rand()), 0, 20);

        $user->validation = $random;
        $user->save();

        $message = __('login.content_reset')."\n".
            __('login.follow_finish')."\n\n"
            .rtrim(config('app.url'), '/') . "/restore" . "/".
            $user->email . "/" . $random . "\n\n".__('login.thanks'); 

        Mail::to($user->email)
			->subject(__('login.reset_confirmation'))
			->plain($message);

        $reg_message = __('login.message_sent').'<br><br>'.
            __('login.follow_reset').'<br><br>'.
            __('login.thanks').'<br>';

        return view('auth/message', compact('title', 'breadcrumb', 'reg_message'));
    }


    public function restore_confirm($email, $token)
    {
        $guard = self::getGuard(config('auth.defaults.guard'));

        $title = __('login.restore');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = Model::instance($guard->getProvider()->getModel())
            ->where('email', $email)
            ->where('validation', $token)
            ->first();

        if (!$user) {
            abort(403);
        }

        $username = $user->username;

        return view('auth/restore', compact('title', 'breadcrumb', 'username', 'token'));
    }

    public function restore_confirmed(Request $request)
    {
        $guard = self::getGuard(config('auth.defaults.guard'));

        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = Model::instance($guard->getProvider()->getModel())
            ->where('username', $request->username)
            ->where('validation', $request->reset_token)
            ->first();

        if (!$user) {
            abort(403);
        }

        $token = md5($user->username.'_'.$user->password.'_'.Carbon::now()->getTimestamp());

        $user->password = md5($request->password);
        $user->token = $token;
        $user->token_timestamp = Carbon::now()->getTimestamp();
        $user->validation = null;
        $user->save();

        return view('auth/completed', compact('title', 'breadcrumb'));
    }

    public static function autoLogin($token)
    {
        $guard = self::getGuard();

        $user = Model::instance($guard->getProvider()->getModel())
            ->where('token', $token)
            ->first();

        if ($user) {
            $guard->login($user);
        }
    }

    /**
     * Creates Auth routes
     */
    public static function routes($routes=array())
    {
        $register = isset($routes['register'])? $routes['register'] : true;
        $reset = isset($routes['reset'])? $routes['reset'] : true;

        Route::get('login', 'Auth@start_login')->name('login')->withoutMiddleware('auth')->middleware('guest');
        Route::post('login', 'Auth@confirm_login')->name('confirm_login')->withoutMiddleware('auth')->middleware('guest');

        if ($register)
        {
            Route::get('register', 'Auth@register')->name('registration')->withoutMiddleware('auth')->middleware('guest');
            Route::post('register', 'Auth@send_register')->name('confirm_registration')->withoutMiddleware('auth')->middleware('guest');
        }

        if ($reset)
        {
            Route::get('reset', 'Auth@reset')->name('reset_password')->withoutMiddleware('auth')->middleware('guest');
            Route::post('reset', 'Auth@restore')->name('send_reset_password')->withoutMiddleware('auth')->middleware('guest');
            Route::get('restore/{email}/{token}', 'Auth@restore_confirm')->name('restore_password')->withoutMiddleware('auth')->middleware('guest');
            Route::post('restore', 'Auth@restore_confirmed')->name('confirm_restore_password')->withoutMiddleware('auth')->middleware('guest');
        }

        if ($register || $reset)
        {
            Route::get('email_confirm/{email}/{token}', 'Auth@confirm')->name('email_confirm')->withoutMiddleware('auth')->middleware('guest');
            Route::get('email_verify', 'Auth@verify')->name('email_verify')->withoutMiddleware('auth')->middleware('guest');
        }

        Route::get('logout', 'Auth@logout')->name('logout');
    }

}

?>