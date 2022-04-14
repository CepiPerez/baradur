<?php

Class App {

    public $result;
    public $action;
    public $code = 200;
    public $type;
    public $filename;
    public $arguments;
    public static $errors = array();
    public static $messages = array();
    public static $localization = null;

    public static function allErrors() { return self::$errors; }

    public static function start() { return Route::start(); }

    public function route()
    {
        $this->result = Route::getRoute(func_get_args());
        return $this;
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
        return isset(self::$errors[$error])? self::$errors[$error] : null;
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

    public static function setSessionErrors($val)
    {
        self::$errors = $val;
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
        global $locale;
        $locale = $lang;
    }

    public static function trans($string, $placeholder=null)
    {
        global $locale;
        list($file, $value) = explode('.', $string);

        $filepath = _DIR_.'/../../resources/lang/'.$locale.'/'.$file.'.php';
        if (file_exists($filepath))
        {
            $lang = include $filepath;
        }
        else
        {
            $filepath = _DIR_.'/../../resources/lang/'.$locale.'.json';
            if (file_exists($filepath))
            {
                $lang = json_decode(file_get_contents($filepath, 'r'), true);
                return $lang[$string] ? $lang[$string] : $string;
            }
            else
            {
                $filepath = _DIR_.'/../../resources/lang/en/'.$file.'.php';
                $lang = include $filepath;
            }
        }

        $result = $lang[$value] ? $lang[$value] : $value;

        if ($placeholder)
        {
            foreach ($placeholder as $key => $val)
                $result = str_replace(':'.$key, $val, $result);
        }

        return $result;
        
    }

    public static function trans_choice($string, $value, $placeholder=null)
    {
        $str = self::trans($string, $placeholder);
        $res = explode('|', $str);

        if (count($res)==2)
        {
            if ($value==1) return $res[0];
            else return $res[1];
        }
        else if (count($res)>2)
        {
            $cons = array();
            foreach($res as $r) {
                preg_match('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', $r, $matches);
                $cons[] = $matches[1];
            }

            $segments = preg_replace('/^[\{\[]([^\[\]\{\}]*)[\}\]]/', '', $res);

            $selected = 0;
            $count = 0;
            foreach ($cons as $range)
            {
                $r = explode(',', $range);
                
                if ($r[1]=='*')
                {
                    if ($value >= $r[0])
                    {
                        $selected = $segments[$count];
                        break;
                    }
                }
                else if ($r==$value)
                {
                    $selected = $segments[$count];
                    break;
                }
                else if (in_array($value, range($r[0], $r[1])))
                {
                    $selected = $segments[$count];
                    break;
                }
                ++$count;
            }
        }
        return $selected;
    }

    public function showFinalResult()
    {
        if ($this->action == 'response')
        {
            //header_remove('Set-Cookie');
            header('HTTP/1.1 '.$this->code);

            if ($this->type == 'json')
            {
                header('Content-Type: application/json');
                echo json_encode($this->result);
            }
            else if ($this->type=='pdf:download' || $this->type=='pdf:inline')
            {
                header('Content-type:application/pdf');
                if ($this->type=='pdf:download')
                    header('Content-disposition: attachment; filename="'.$this->filename.'"');
                else
                    header('Content-disposition: inline; filename="'.$this->filename.'"');
                header('content-Transfer-Encoding:binary');
                header('Accept-Ranges:bytes');
                if (file_exists($this->result))
                    @readfile($this->result);
            }

        }
        elseif ($this->action == 'redirect')
        {
            echo header('Location: '.$this->result);
        }
        else
        {
            echo $this->result;
        }
    }


}