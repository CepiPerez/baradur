<?php

class Auth extends Controller
{
    protected $primaryKey = 'username';
    protected static $useAuth = false;
    protected static $_currentUser = null;

    public static function user()
    {
        if (!isset(self::$_currentUser) && isset($_SESSION['user']))
            self::$_currentUser = $_SESSION['user'];

        return self::$_currentUser;
    }


    public static function check()
    {
        $user = self::user();
        return isset($user);
    }

    public function id()
    {
        return Auth::user()->getKey();
    }

    public function login($referer = null)
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

    public static function api_login($username, $password)
    {
        $user = Model::instance('User')->where('email', $username)
                    ->orWhere('username', $username)->first();

        if (!$user || strcmp($user->password, md5($password))) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(array("error"=>"Access denied. Bad credentials"));
            exit();
        }

        if (!$user->token) {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(array("error"=>"Access denied. User validation required!"));
            exit();
        }

        $token = md5($user->username.'_'.$user->password.'_'.Carbon::now()->getTimestamp());
        $user->token = $token;
        $user->token_timestamp = Carbon::now()->getTimestamp();
        $user->save();

        return $token;

    }

    /* private static function generateToken($user = null)
    {
        $token = hash_hmac('sha256', $user, bin2hex(random_bytes(32)));
        $date = new DateTime;
        $timestamp = $date->format('Y-m-d H:i:s');

        DB::statement('CREATE TABLE IF NOT EXISTS api_tokens (`token` VARCHAR(100), `timestamp` TIMESTAMP)');

        DB::statement('INSERT INTO api_tokens (token, timestamp)'. ' VALUES ("' . $token . '", "' .$timestamp . '")');

        return $token;

    } */


    public function send_login(Request $request)
    {
        $user = Model::instance('User')->where('email', $request->username)
                    ->orWhere('username', $request->username)->first();

        if (strcmp($user->password, md5($request->password)) || !$user) {
            return back()->with("error", __("login.invalid"));
        }

        if (isset($user->token)) {
            return back()->with("error", __("login.validation_required"));
        }

        $token = md5($user->username.'_'.$user->password.'_'.Carbon::now()->getTimestamp());
        $user->token = $token;
        $user->token_timestamp = Carbon::now()->getTimestamp();
        $user->save();

        if ($request->remember)
        {
            $domain = $_SERVER["HTTP_HOST"];
            setcookie(env('APP_NAME').'_token', $token, time()+86400, '/'.env('APP_FOLDER'), $domain, false, true);
        }

        $user->unsetAttribute('password');
        $user->unsetAttribute('validation');

        $_SESSION['user'] = $user; //Helpers::arrayToObject($user->getAttributes());
        self::$_currentUser = $user;

        if (isset($_SESSION['_requestedRoute']))
        {
            $res = $_SESSION['_requestedRoute'];
            unset($_SESSION['_requestedRoute']);
            $res = str_replace(env('APP_URL'), '', $res);
            return redirect($res);
        }

        return redirect(env('APP_URL'));

    }

    public function logout()
    {
        $user = Model::instance('User')->find(auth()->id());
        $user->token = null;
        $user->save();

        $domain = $_SERVER["HTTP_HOST"];
        setcookie(env('APP_NAME').'_token', '', time() - 3600, '/'.env('APP_FOLDER'), $domain);

        unset($_SESSION['user']);
        unset($_SESSION['tokens']);
        self::$_currentUser = null;

        return back();
    }

    public function register()
    {
        unset($_SESSION['user']);
        unset($_SESSION['tokens']);
        self::$_currentUser = null;

        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        return view('auth/register', compact('title', 'breadcrumb'));
    }

    public function send_register(Request $request)
    {
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
            .rtrim(env('APP_URL'), '/') . "/email_confirm" . "/".
            $request->email . "/" . $random . "\n\n".__('login.thanks'); 
    
        Mail::to($request->email)
			->subject(__('login.register_confirmation'))
			->plain($message);

        $user = new User;
        $user->username = $request->username;
        $user->password = md5($request->password);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->validation = $random;

        $res = $user->save();

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
        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = Model::instance('User')->where('email', $email)
                    ->where('validation', $token)->first();

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
        $request->validate(array(
            'username' => 'required'
        ));

        $title = __('login.message_sent');
        
        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = Model::instance('User')->where('username', $request->username)
                ->orWhere('email', $request->username)->first();

        if (!$user)
        {
            return back()->with("error", __('login.no_user'));
        }

        $random = substr(md5(rand()), 0, 20);

        $user->validation = $random;
        $user->save();

        $message = __('login.content_reset')."\n".
            __('login.follow_finish')."\n\n"
            .rtrim(env('APP_URL'), '/') . "/restore" . "/".
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
        $title = __('login.restore');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = Model::instance('User')->where('email', $email)
                    ->where('validation', $token)->first();

        if (!$user)
        {
            abort(403);
        }

        $username = $user->username;

        return view('auth/restore', compact('title', 'breadcrumb', 'username', 'token'));
    }

    public function restore_confirmed(Request $request)
    {
        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = Model::instance('User')->where('username', $request->username)
                    ->where('validation', $request->reset_token)->first();

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
        //dd("AUTOLOGIN: $token");
        $user = Model::instance('User')->where('token', $token)->first();

        if ($user)
        {
            $domain = $_SERVER["HTTP_HOST"];
            setcookie(env('APP_NAME').'_token', $user->token, time()+86400, '/'.env('APP_FOLDER'), $domain, false, true);
            unset($user->password);
            unset($user->validation);
            self::$_currentUser = $user;
            $_SESSION['user'] = $user;
        }
    }

    /**
     * Creates Auth routes
     */
    public static function routes($routes=array())
    {
        $register = isset($routes['register'])? $routes['register'] : true;
        $reset = isset($routes['reset'])? $routes['reset'] : true;

        Route::get('login', 'Auth@login')->name('login')->withoutMiddleware('auth');
        Route::post('login', 'Auth@send_login')->name('confirm_login')->withoutMiddleware('auth');

        if ($register)
        {
            Route::get('register', 'Auth@register')->name('registration')->withoutMiddleware('auth');
            Route::post('register', 'Auth@send_register')->name('confirm_registration')->withoutMiddleware('auth');
        }

        if ($reset)
        {
            Route::get('reset', 'Auth@reset')->name('reset_password')->withoutMiddleware('auth');
            Route::post('reset', 'Auth@restore')->name('send_reset_password')->withoutMiddleware('auth');
            Route::get('restore/{email}/{token}', 'Auth@restore_confirm')->name('restore_password')->withoutMiddleware('auth');
            Route::post('restore', 'Auth@restore_confirmed')->name('confirm_restore_password')->withoutMiddleware('auth');
        }

        if ($register || $reset)
        {
            Route::get('email_confirm/{email}/{token}', 'Auth@confirm')->name('email_confirm')->withoutMiddleware('auth');
        }

        Route::get('logout', 'Auth@logout')->name('logout');
    }

}

?>