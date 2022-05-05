<?php

class Auth extends Controller
{
    protected $tokenVerification = false;
    protected $primaryKey = 'username';
    protected static $useAuth = false;

    public static function user()
    {
        #unset($_SESSION['user']);
        if (isset($_SESSION['user']))
        {
            $usuario =  $_SESSION['user'];
            /* $usuario = User::where('username', $_SESSION['user']['username'])->first();
            return $usuario; */
            /* $usuario = new Collection('User');
            $usuario = $usuario->arrayToObject($_SESSION['user']); */

            //dd($usuario);
            return $usuario;
        }
        return null;
    }

    public static function check()
    {
        return isset($_SESSION['user']);
    }

    public function handle($request, $next)
    {
        if (!Auth::check())
        {
            $_SESSION['_requestedRoute'] = $request->fullUrl();
            return redirect('login');
        }
        return $next;
    }

    public function login()
    {
        if (isset($_SESSION['url_history'][1]) && !isset($_SESSION['_requestedRoute']))
            $_SESSION['_requestedRoute'] = $_SESSION['url_history'][1];

        $title = __('login.login');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.login') => '#'
        );

        return view('auth/login', compact('title', 'breadcrumb'));
    }

    public function send_login($request)
    {
        $user = User::where('email', $request->username)
                    ->orWhere('username', $request->username)->first();


        if (strcmp($user->password, md5($request->password)) || !$user)
        {
            return back()->with("error", __("login.invalid"));
        }
        else if (isset($user->validation))
        {
            return back()->with("error", __("login.validation_required"));
        }
        else
        {
            $token = md5($user->username.'_'.$user->password.'_'.time());
            User::where('username', $user->username)
                        ->update(array(
                            "token" => $token,
                            'token_timestamp' => time()
                        ));

            $domain = $_SERVER["HTTP_HOST"];
            setcookie(env('APP_NAME').'_token', $token, time()+86400, '/'.env('APP_FOLDER'), $domain, false, true);
            unset($user->password);
            unset($user->validation);
            $_SESSION['user'] = $user;

            if (isset($_SESSION['_requestedRoute']))
            {
                $res = $_SESSION['_requestedRoute'];
                unset($_SESSION['_requestedRoute']);
                return redirect($res);
            }
            else
                return redirect('/');
        }

    }

    public function logout()
    {
        global $version;

        $domain = $_SERVER["HTTP_HOST"];
        setcookie(env('APP_NAME').'_token', '', time() - 3600, '/'.env('APP_FOLDER'), $domain);

        if ($version == 'NEW') {
            if (session_status() === PHP_SESSION_ACTIVE)
                session_destroy();
        } else {
            if (isset($_SESSION['user']))
                unset($_SESSION['user']);
        }
        
        /* unset($_SESSION['user']);
        unset($_SESSION['tokens']); */
        return back();
    }

    public function register()
    {
        unset($_SESSION['user']);
        unset($_SESSION['tokens']);

        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        return view('auth/register', compact('title', 'breadcrumb'));
    }

    public function send_register($request)
    {

        $title = __('login.message_sent');

        $random = substr(md5(rand()), 0, 10);

        $message = __('login.content_registration')."\n".
                    __('login.follow_finish')."\n\n"
                    .rtrim('/', "/") ."/email_confirm" . "/".
                    $request->email . "/" . $random . "\n\n".__('login.thanks'); 

        mail($request->email, __('login.register_confirmation'), $message);
        

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

        $reg_message = __('login.follow_registration').'<br><br>'.
                        __('login.thanks').'<br>';

        return view('auth/message', compact('title', 'breadcrumb', 'reg_message'));
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

    public function restore($request)
    {
        $title = __('login.message_sent');
        
        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );

        $user = User::where('username', $request->username)
                ->orWhere('email', $request->username)->first();

        if ($user)
        {
            $random = substr(md5(rand()), 0, 10);

            $message = __('login.content_reset')."\n".
                        __('login.follow_finish')."\n\n"
                        ."/email_confirm" . "/".
                        $request->email . "/" . $random . "\n\n".__('login.thanks'); 
    
            mail($request->email, __('login.reset_confirmation'), $message);

            User::where('username', $user->username)
                    ->update(array('clave' => md5($request->password), 'validacion' => $random));

            $reg_message = __('login.follow_reset').'<br><br>'.
                        __('login.thanks').'<br>';

            return view('auth/message', compact('title', 'breadcrumb', 'reg_message'));
        }
        else
        {            
            return back()->with("error", __('login.no_user'));
        }

    }

    public function confirm($email, $token)
    {
        $title = __('login.registration');

        $breadcrumb = array(
            __('login.home') => '/',
            __('login.registration') => '#'
        );


        $user = User::where('email', $email)
                    ->where('validacion', $token)->first();

        if (!$user)
        {
            abort(403);
        }
        else
        {
            User::where('usuario', $user->username)
                        ->update(array('validacion'=>null));
        }

        return view('auth/completed', compact('title', 'breadcrumb'));
    }

    public static function autoLogin($token)
    {
        $user = User::where('token', $token)->first();

        if (isset($user->username))
        {
            unset($user->password);
            unset($user->validation);
            $_SESSION['user'] = $user;
        }

    }

    /**
     * Creates Auth routes
     * 
     */
    public static function routes($routes=array())
    {
        $register = isset($routes['register'])? $routes['register'] : true;
        $reset = isset($routes['reset'])? $routes['reset'] : true;

        Route::get('login', 'Auth@login')->name('login');
        Route::post('login', 'Auth@send_login')->name('confirm_login');

        if ($register)
        {
            Route::get('register', 'Auth@register')->name('registration');
            Route::post('register', 'Auth@send_register')->name('confirm_registration');
        }

        if ($reset)
        {
            Route::get('reset', 'Auth@reset')->name('reset_password');
            Route::post('reset', 'Auth@restore')->name('confirm_reset_password');
        }

        if ($register || $reset)
        {
            Route::get('email_confirm/{email}/{token}', 'Auth@confirm')->name('email_confirm');
        }

        Route::get('logout', 'Auth@logout')->name('logout');
    }

}

?>