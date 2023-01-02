<?php

Class App {

    public $result;
    public $action;
    public $code = 200;
    public $type;
    public $filename;
    public $arguments;
    public $inline;
    public $headers;
    //public static $errors = array();
    public static $messages = array();
    public static $localization = null;

    public $binds = array();

    //public static function allErrors() { return self::$errors; }

    public static function start() { 

        # Autologin
        if (!isset($_SESSION['user']))
        {
            if (isset($_COOKIE[env('APP_NAME').'_token']) && !Auth::user() && Route::has('login'))
            {
                Auth::autoLogin($_COOKIE[env('APP_NAME').'_token']);
            }
        }      
        return Route::start();
    }

    public function inProduction()
    {
        return env('APP_ENV')=='production';
    }

    public function route()
    {
        $this->result = Route::getRoute(func_get_args());
        return $this;
    }

    public function json($json=null)
    {
        if ($json) $this->result = $json;
        return $this;
    }

    public function __call($method, $parameters)
    {
        if (! Str::startsWith($method, 'with')) {
            throw new Exception("Method [$method] does not exist on view.");
        }

        return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
    }

    public function with($key, $value)
    {
        $_SESSION['messages'][$key] = $value;
        return $this;
    }

    public function withErrors($errors)
    {
        foreach ($errors as $key => $val)
            $_SESSION['errors'][$key] = $val;

        return $this;
    }

    public static function getError($error)
    {
        global $errors; 
        return $errors->$error;
    }

    /* public function setError($name, $message)
    {
        $_SESSION['errors'][$name] = $message;
    } */

    public static function getSession($val)
    {
        return isset(self::$messages[$val])? self::$messages[$val] : null;
    }

    public static function setSessionMessages($val)
    {
        self::$messages = $val;
    }


    public static function generateToken()
    {
        $timestamp = date('Y-m-d H:i:s');
        $csrf = hash_hmac('sha256', Route::getCurrentRoute()->url, $_SESSION['key']);
        $_SESSION['tokens'][$csrf]['timestamp'] = $timestamp;
        $_SESSION['tokens'][$csrf]['counter'] = 1;
        return $csrf;
    }

    public static function setLocale($lang)
    {
        /* global $locale;
        $locale = $lang; */
    }

    public function render()
    {
        return serialize($this);
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        $this->binds[$abstract] = array(
            'concrete' => $concrete, 
            'shared' => $shared
        );
    }

    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public static function instance($name = null)
    {
        global $app;

        if (!isset($name))
        {
            return $app;
        }

        //dd($app->binds);

        if (isset($app->binds[$name]))
        {
            if ($app->binds[$name]['shared'])
            {
                if (!isset($app->binds[$name]['instance']))
                {
                    $class = $app->binds[$name]['concrete'];
                    $app->binds[$name]['instance'] = new $class;
                }

                return $app->binds[$name]['instance'];
            }
            
            else
            {
                $class = $app->binds[$name]['concrete'];
                return new $class;
            }
        }

        foreach ($app->binds as $key => $val)
        {
            if ($app->binds[$key]['concrete'] == $name)
            {
                if (!isset($app->binds[$key]['instance']))
                {
                    $class = $app->binds[$key]['concrete'];
                    $app->binds[$key]['instance'] = new $class;
                }

                return $app->binds[$key]['instance'];

            }

        }

        return new $name;
    }


    public function showFinalResult()
    {
        if ($this->action == 'response')
        {
            #dd($this);exit();
            header('HTTP/1.1 '.$this->code);

            if ($this->result instanceof ResourceCollection)
            {
                $this->result = $this->result->getResult();
            }
    
            if ($this->type == 'application/json')
            {
                header('Content-Type: application/json');
                
                /* if (is_object($this->result) && get_class($this->result)=='Collection')
                    $this->result = $this->result->toArray(); */

                echo json_encode($this->result);
            }
            else
            {
                if (isset($this->headers))
                {
                    foreach ($this->headers as $header)
                        header($header);
                }
                else
                {
                    header('Content-type:'.$this->type);
                    header('Content-disposition: '. ($this->inline?'inline':'download') .'; filename="'.$this->filename.'"');
                    header('content-Transfer-Encoding:binary');
                    header('Accept-Ranges:bytes');
                }
                if (file_exists($this->result))
                    @readfile($this->result);
            }

        }
        elseif ($this->action == 'redirect')
        {
            echo header('Location: '.$this->result);
        }
        elseif (is_object($this->result))
        {
            //dd($this->result);

            $view = View::loadTemplate($this->result->template, $this->result->arguments);

            if (env('DEBUG_INFO')==1)
            {
                global $debuginfo;
                $size = memory_get_usage();
                $debuginfo['memory_usage'] = get_memory_converted($size);
                $params['debug_info'] = $debuginfo;

                $start = $debuginfo['start'];
                $end = microtime(true) - $start;
                $debuginfo['time'] = number_format($end, 2) ." seconds";

                $script = '<script>var debug_info = '."[".json_encode($debuginfo)."]"."\n".
                    '$(document).ready(function(e) {
                        console.log("TIME: "+debug_info.map(a => a.time));
                        console.log("MEMORY USAGE: "+debug_info.map(a => a.memory_usage));
                        let q = debug_info.map(a => a.queryes);
                        if (q[0]) {
                        q[0].forEach(function (item, index) {
                            console.log("Query #"+(index+1));
                            console.log(item);
                        });
                        }
                    });</script>';
                $view = str_replace('</body>', $script."\n".'</body>', $view);

            }

            echo $view;
        }
    }


}