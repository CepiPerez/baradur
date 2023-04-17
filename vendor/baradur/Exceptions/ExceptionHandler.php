<?php

Class ExceptionHandler
{
    private $exception;

    protected $dontFlash = array();

    private $renderable = array();

    public static function getInstance($exception)
    {
        $instance = new Handler($exception);

        $instance->register();

        $instance->handleException();
    }

    public function __construct($exeption)
    {
        $this->exception = $exeption;
    }

    public function register()
    {

    }

    public function setException($exception)
    {
        $this->exception = $exception;

        return $this;
    }

    public function getDontFlash()
    {
        return $this->dontFlash;
    }

    public function renderable($callback)
    {
        $this->renderable[] = $callback;
    }
    
    public function handleException()
    {
        ob_end_clean();

        unset($this->exception->xdebug_message);

        if (method_exists($this->exception, 'render')) {
            $response = $this->exception->render(request());

            echo CoreLoader::processResponse($response);
            
            __exit();
        }

        if (!config('app.debug')) {
            foreach ($this->renderable as $callback) {
                list($class, $method) = getCallbackFromString($callback);

                $class = new $class($this->exception);
                $res = executeCallback($class, $method, array($this->exception, request()), $class);
                
                if ($res) {
                    echo CoreLoader::processResponse($res);
                    __exit();
                }
            }

            if (!request()->expectsJson()) {
                echo $this->generateView();

                __exit();
            }
        }
        
        if (request()->expectsJson()) {
            echo $this->generateJson();

            __exit();
        }

        echo $this->generateException();
        
        __exit();
    }

    private function generateView()
    {
        $code = 0;

        if (method_exists($this->exception, 'getStatusCode')) {            
            $code = $this->exception->getStatusCode();
        } else {
            $code = $this->exception->getCode();
        }

        if ($code==0) $code = 404;

        $message = $this->exception->getMessage();

        if (!$message || $message=='') {
            $message = HttpResponse::$reason_phrases[$code];
        }

        return View::showErrorTemplate($code, $this->exception->getMessage());
    }

    public function generateJson()
    {
        if (method_exists($this->exception, 'getStatusCode')) {            
            $code = $this->exception->getStatusCode();
        } else {
            $code = $this->exception->getCode();
        }

        if ($code==0) $code = 404;

        $result = array();

        $result["message"] = $this->exception->getMessage();

        if (!in_array($code, array(400, 401, 402, 403, 404, 419, 429, 500, 503))) {
            $result["code"] = 500;
            $result["message"] = __('Server Error');
        }

        if (config('app.debug')) {
            $result["exception"] = get_class($this->exception);
            $result["file"] = $this->exception->getFile();
            $result["line"] = $this->exception->getLine();
            $result["trace"] = $this->exception->getTrace();
        }

        return CoreLoader::processResponse(response($result, $code));
    }

    public static function getClassFilename($class)
    {
        if (str_contains($class, '/storage/framework/views')) {
            return '<i style="opacity:50%;margin-right:.5rem;">(compiled)</i>'. base64url_decode(basename($class));
        }

        if (str_contains($class, '/storage/framework/classes')) {
            global $_class_list;
            $real = $_class_list[self::getClassBasename($class)];
            return '<i style="opacity:50%;margin-right:.5rem;">(compiled)</i>' . str_replace(_DIR_, '', $real);
        }

        return str_replace(_DIR_, '', $class);
    }

    public static function getClassBasename($class)
    {
        if (str_contains($class, '/storage/framework/views')) {
            $class = base64url_decode(basename($class));
        }

        $class = str_replace('.blade.php', '', $class);

        $class = pathinfo($class);
        $class = $class['basename'];
        $class = str_ireplace('.php', '', $class);

        return $class;
    }

    public static function showException($exception)
    {
        $blade = new BladeOne(_DIR_.'vendor/baradur/Exceptions/views', _DIR_.'storage/framework/views');

        global $debuginfo;

        $message = $exception->getMessage();

        if (!$message || $message=='') {
            $message = HttpResponse::$reason_phrases[$exception->getCode()];
        }

        //dd(request());

        $trace = array();

        $line = $exception->getLine();
        $class = $exception->getFile();
        $basename = self::getClassBasename($class);

        $currentTab = 0;

        $trace[$class."@".$line] = array(
            'function' => null,
            'file' => $class,
            'line' => $line,
            'basename' => $basename,
            'content' => Helpers::loadFile($class, intval($line)-10, intval($line)+10),
            'vendor' => str_contains($class, '/vendor/')? '1' : '0'
        );

        if (!str_contains($class, '/vendor/')) {
            $currentTab = 1;
        }

        $loop = 2;
        foreach ($exception->getTrace() as $tr)
        {
            if ($tr['file']) {
                $line = $tr['line'];
                $class = $tr['file'];
                $basename = self::getClassBasename($class);
        
                if(!isset($trace[$class."@".$line])) {
                    $trace[$class."@".$line] = array(
                        'function' => $tr['function'],
                        'file' => $class,
                        'line' => $line,
                        'basename' => $basename,
                        'content' => Helpers::loadFile($class, intval($line)-10, intval($line)+10),
                        'vendor' => str_contains($class, '/vendor/')? '1' : '0'
                    );

                    if (!str_contains($class, '/vendor/') && $currentTab==0) {
                        $currentTab = $loop;
                    }

                    $loop++;
                }
            }
        }

        //dd($exception, $message, self::canBeSolved($exception), ExceptionSolutionHelper::getSolution($exception));

        $result = $blade->runInternal(
            'exception', array(
                'exception' => $exception,
                'message' => $message,
                'query' => self::isQueryException($exception)
                    ? end($debuginfo['queryes'])
                    : null,
                'solution' => self::canBeSolved($exception)
                    ? ExceptionSolutionHelper::getSolution($exception)
                    : null,
                'trace' => $trace,
                'currentTab' => $currentTab
            ),
            true
        );


        return CoreLoader::processResponse(response($result, 404));
    }

    private static function canBeSolved($exception)
    {
        if (get_class($exception)=='QueryException') {
            return Str::contains($exception->getMessage(), array('Unknown column', "doesn't exist", 'not found'));
        }

        return true;
    }

    private static function isQueryException($exception)
    {
        return in_array(get_class($exception), array(
            'QueryException',
            'ModelNotFoundException',
            'RecordsNotFoundException',
            'MultipleRecordsFoundException'
        ));
    }

    private function generateException()
    {
        return self::showException($this->exception);
    }

}