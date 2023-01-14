<?php

function app($val=null) { global $app; return $app->instance($val); }
function asset($val) { return View::getAsset($val); }
function route() { return Route::getRoute(func_get_args()); }
function session($val) { return App::getSession($val); }

function __($translation, $placeholder=null) { return Helpers::trans($translation, $placeholder); }
function public_path($path=null) { return env('APP_URL').'/'.env('PUBLIC_FOLDER').'/'.$path; }
function storage_path($path=null) { return _DIR_.'storage/'.$path; }
function base_path($path=null) { return _DIR_.$path; }
function csrf_token() { return App::generateToken(); }
function config($val) { return Helpers::config($val); }
function to_route($route) { return redirect()->route($route); }
function class_basename($name) { return get_class($name); }
function abort_if($condition, $code) { if ($condition) abort($code); }
function abort_unless($condition, $code) { if (!$condition) abort($code); }
function validator($data, $rules, $messages=array()) { return new Validator($data, $rules, $messages); }

/** @return Auth */ 
function auth() { return new Auth; }

/** @return Stringable */ 
function str($string=null) { if (!$string) return new Str; else return Str::of($string); }

/** @return Collection */ 
function collect($data=array()) { $col = new Collection('stdClass'); return $col->collect($data); }

/** @return Faker */ 
function fake() { return new Faker; }

/** @return Carbon */ 
function now() { return Carbon::now(); }

/** @return Carbon */ 
function today() { return Carbon::today(); }

/** @return Request */ 
function request() { return app('request'); }


$errors = new MessageBag();

/**
 * Returns the template inside a string\
 * Example: loadView('products', compact())
 * 
 * @param string $template Template file (without .blade.php)
 * @param string $params Parameters to send to template 
 * @return string
 */
function loadView($template, $params=array())
{
	return View::loadTemplate($template, $params);
}

function retry($times, $callback, $sleepMilliseconds=0, $when=null)
{ 
	return RetryHelper::retry($times, $callback, $sleepMilliseconds, $when); 
}


function get_memory_converted($size) {
	$unit=array('b','kb','mb','gb','tb','pb');
	return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

$temp_params = null;
/**
 * Returns the template\
 * Example: view('products', compact())
 * 
 * @param string $template Template file (without .blade.php)
 * @param string $params Parameters to send to template 
 * @return App
 */
function view($template, $params=null)
{
	global $app, $temp_params, $debuginfo;
	$app->action = 'show';

	if (!isset($params) && isset($temp_params))
		$params = $temp_params;
	
	/* $view = View::loadTemplate($template, $params);

	if (env('DEBUG_INFO')==1)
	{

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

	$app->result = $view; */
	$app->result = new FinalView($template, $params);

	return $app;
}

/**
 * Shows the error page\
 * Example: abort(403)
 * 
 * @param string $error
 */
function abort($error)
{
	$errormsg = HttpResponse::$reason_phrases[$error];

	error($error, $errormsg);
}

/**
 * Shows the error page with custom number and message\
 * Example: error(666, 'Unexpected error')
 * 
 * @param string $error
 * @param string $message
 */
function error($error_code, $error_message)
{

	if (file_exists(_DIR_.'resources/views/errors/'.$error_code.'.blade.php'))
	{
		view('errors.'.$error_code, compact('error_code', 'error_message'));
		app()->showFinalResult();
		die();
	}

	view('layouts.error', compact('error_code', 'error_message', 'breadcrumb'));
	app()->showFinalResult();
	die();
}


/**
 * Returns to previous page if no parameter is defined
 * or eturns back the number of pages 
 * Example: error(666, 'Unexpected error')
 * 
 * @param string $pages
 * @return App
 */
function back($nums = 1)
{
	if (isset($_POST)) $_SESSION['old'] = $_POST;

	//print '<script>window.history.go(-'.$nums.');</script>';
	//exit();
	global $app;
	--$nums;

	$app->action = 'redirect';
	//$app->result = '<script>history.back(-3);</script>';
	if (isset($_SERVER["HTTP_REFERER"]) && $nums==0)
        $app->result = $_SERVER["HTTP_REFERER"];
    else
		$app->result = $_SESSION['url_history'][$nums];
	
	return $app;
}

/**
 * Clears the old() helper
 * 
 * @return App
 */
function clearInput()
{
	global $app;
	unset($_SESSION['old']);
	return $app;
}


/**
 * Redirects to defined url\
 * Url can be ommited if combined with route()\ 
 * Example: redirect('/products/info')
 * 
 * @param string $url
 * @return App
 */
function redirect($url=null)
{
	if (isset($_POST)) $_SESSION['old'] = $_POST;

	global $app;
	$app->action = 'redirect';
	if ($url) $app->result = $url;
	return $app;
}


/**
 * Returns a response with data\
 * Default response type is JSON\ 
 * Example: response($data, '200 OK')
 * 
 * @param string $data
 * @param string $code
 * @param string $type
 * @param string $filename
 * @return App
 */
function response($data=null, $code='200', $type='application/json', $filename=null, $inline=false, $headers=null)
{
	global $app;

	# Removing hidden attributes from response
	if (is_object($data) && get_class($data)=='Collection')
	{
		$data = $data->toArray();
	}
	elseif (is_object($data) && is_subclass_of($data, 'Model'))
	{
		$data = $data->toArray();
	}

	$app->result = $data;
	$app->action = 'response';
	$app->type = $type;
	$app->code = $code;
	$app->filename = $filename;
	$app->inline = $inline;
	$app->headers = $headers;

	return $app;
}

$__currentArray = 0;

function ddd($data)
{
	dump($data, true, true);
}

function dd($data)
{
	dump($data, false, true);
}

function dump($data, $full=false, $die=false)
{
	/* highlight_string("<?php\n" . print_r($data, true) . ";?>"); exit(); */
	global $_model_list;
	$res = PrettyDump::getDump($data, $full, array('Model' => $_model_list, 'Collection'=> 'Collection'));

	if ($die) {
		if (env('DEBUG_INFO')==1)
		{
			global $debuginfo;
			$size = memory_get_usage();
			//$peak = memory_get_peak_usage();
			$debuginfo['memory_usage'] = get_memory_converted($size);
			//$debuginfo['memory_peak'] = get_memory_converted($peak);
			//$params['debug_info'] = $debuginfo;
	
			$start = $debuginfo['start'];
			$end = microtime(true) - $start;
			$debuginfo['time'] = number_format($end, 2) ." seconds";
	
			$script = "\n".'<script src="'.asset('assets/js/jquery-3.5.1.min.js') .'"></script>'."\n".
				'<script>var debug_info = '."[".json_encode($debuginfo)."]"."\n".
				'$(document).ready(function(e) {
					console.log("TIME: "+debug_info.map(a => a.time));
					console.log("MEMORY USAGE: "+debug_info.map(a => a.memory_usage));
					//console.log("MEMORY PEAK: "+debug_info.map(a => a.memory_peak));
					let q = debug_info.map(a => a.queryes);
					if (q[0]) {
					  q[0].forEach(function (item, index) {
						console.log("Query #"+(index+1));
						console.log(item);
					  });
					}
				});</script>';
			echo $script;
		}
		die();
	}
}

function csrf_field()
{
	return "<input type='hidden' name='csrf' value='".csrf_token()."'/>\n";
}

function method_field($v)
{
	return "<input type='hidden' name='_method' value='$v'/>\n";
}

function js_str($s)
{
    return '"' . addcslashes($s, "\0..\37\"\\") . '"';
}

function js_array($array)
{
    $temp = array_map('js_str', $array);
    return '[' . implode(',', $temp) . ']';
}

function blank($value)
{
	if (!isset($value))
		return true;

	if (is_string($value) && trim($value)=='')
		return true;

	if ($value instanceof Collection)
		$value = $value->toArray();

	if (is_array($value) && count($value)==0)
		return true;

	return false;		
}

function filled($value)
{
	return !blank($value);
}

function cache($value=null, $time=null)
{
	$cache = Cache::store();

	if (isset($value) && is_string($value))
	{
		return $cache->get($value);
	}
	
	if (isset($value) && is_array($value))
	{
		foreach ($value as $key => $val)
		{
			if ($cache instanceof FileStore)
				$res = $cache->put($key, $val, $time? (int)$time : 60);
			else
				$res = $cache->put($key, $val);
		}
		return $res;
	}
	
	return $cache;

}

function tap($value, $callback=null)
{
	if (is_null($callback)) {
		return new HigherOrderTapProxy($value);
	}

	if (strpos($callback, '@')===false) {
		throw new Exception("Invalid callback for tap() method");
	}

	list($class, $method, $params) = getCallbackFromString($callback);
	array_shift($params);
	call_user_func_array(array($class, $method), array_merge(array($value), $params));
	
	return $value;
}

function value($default, $parent=null)
{
	if (is_string($default) && strpos($default, '@')!==false)
	{
		list($class, $method, $params) = getCallbackFromString($default);
		return call_user_func_array(array($class, $method), $parent? array_merge(array($parent), $params) : $params);
	}

	return $default;
}

function app_path($value=null)
{
	return _DIR_.'app/'.$value;
}

function is_assoc($arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}