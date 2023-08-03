<?php

function app($val=null) { global $app; return $app->instance($val); }
function asset($val) { return View::getAsset($val); }
function route() { return Route::getRoute(func_get_args()); }
function to_route() { return redirect(Route::getRoute(func_get_args())); }
function __($translation, $placeholder=null) { return Helpers::trans($translation, $placeholder); }
function public_path($path=null) { return config('app.url').'/'.$path; }
function storage_path($path=null) { return _DIR_.'storage/'.$path; }
function base_path($path=null) { return _DIR_.$path; }
function csrf_token() { return App::getRequestToken(); }
function config($val) { return Helpers::config($val); }
function class_basename($name) { return get_class($name); }
function abort_if($condition, $code) { if ($condition) abort($code); }
function abort_unless($condition, $code) { if (!$condition) abort($code); }
function validator($data, $rules, $messages=array()) { return new Validator($data, $rules, $messages); }
function bcrypt($password) { return Hash::make($password); }

/** @return Auth */ 
function auth() { return new Auth; }

/** @return mixed */ 
function session($key=null, $default=null) { return request()->session($key, $default); }

/** @return Stringable */ 
function str($string=null) { if (!$string) return new Stringable; else return Str::of($string); }

/** @return Collection */ 
function collect($data=array()) { return new Collection($data); }

/** @return Faker */ 
function fake() { return new Faker; }

/** @return Carbon */ 
function now() { return Carbon::now(); }

/** @return Carbon */ 
function today() { return Carbon::today(); }

/** @return Request */ 
function request() { return app('request'); }

function old($value, $default=null) { 
	$old = session('_old', array());
	return array_key_exists($value, $old) ? $old[$value] : $default;
}

$errors = new MessageBag();

function __exit($status=null)
{
	define('EXIT_OK', TRUE);
	exit($status);
}


/**
 * Returns the template inside a string\
 * Example: loadView('products', compact())
 * 
 * @param string $template Template file (without .blade.php)
 * @param array $params Parameters to send to template 
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
 * @param array $params Parameters to send to template 
 * @return FinalView
 */
function view($template=null, $params=null)
{
	global $temp_params;

	if (!isset($params) && isset($temp_params)) {
		$params = $temp_params;
	}
	
	return new FinalView($template, $params);
}

/**
 * Shows the error page\
 * Example: abort(403)
 * 
 * @param string $error
 */
function abort($code, $message=null)
{
	if (!$message && !in_array($code, array(401, 402, 403, 404, 419, 429, 500, 503))) {
		$message = HttpResponse::$reason_phrases[$code];
	}

	error($code, $message);
}

/**
 * Shows the error page with custom number and message\
 * Example: error(666, 'Unexpected error')
 * 
 * @param string $error
 * @param string $message
 */
function error($code, $message)
{
	/* if (file_exists(_DIR_.'resources/views/errors/'.$error_code.'.blade.php')) {
		$res = view('errors.'.$error_code, compact('error_code', 'error_message'));
	} else {
		$res = view('layouts.error', compact('error_code', 'error_message', 'breadcrumb'));
	}

	$response = response( (string)$res, $error_code);
	echo $response->body(); exit();  */
		
	switch ((int)$code) {
        case 401: 
			throw new UnauthorizedHttpException('Basic', $message? $message : __('Unauthorized'), null, 401);
		case 403:
			throw new AccessDeniedHttpException($message? $message : __('Forbidden'), null, 403);
		case 402:
			throw new PaymentRequiredHttpException($message? $message : __('Payment Required'), null, 403);
		case 404: 
			throw new NotFoundHttpException($message? $message : __('Not Found'), null, 404);
		case 419: 
			throw new PageExpiredHttpException($message? $message : __('Page Expired'), null, 419);
		case 429: 
			throw new TooManyRequestsHttpException(null, $message? $message : __('Too Many Requests'), null, 429);
		case 500: 
			throw new ServerErrorHttpException($message? $message : __('Server Error'), null, 500);
		case 503: 
			throw new ServiceUnavailableHttpException($message? $message : __('Service Unavailable'), null, 503);
					
		default: 
			throw new RuntimeException($message, $code);
    }
}


/**
 * Returns to previous page if no parameter is defined
 * or eturns back the number of pages 
 * Example: error(666, 'Unexpected error')
 * 
 * @param string $pages
 * @return Response
 */
function back()
{
	$old = array();

	if (isset($_POST)) {

		$handler = new Handler(new Exception());
		$dontFlash = $handler->getDontFlash();
		$dontFlash = array_merge($dontFlash, array('csrf', '_method'));

		foreach ($_POST as $key => $val) {
			if (!in_array($key, $dontFlash)) {				
				$old[$key] = $val;
			}
		}
	}

	//$_SESSION['old'] = $old;
	session()->flash('_old', $old);

	if (isset($_SERVER["HTTP_REFERER"])) {
        $address = $_SERVER["HTTP_REFERER"];
	} else {
		$address = $_SESSION['url_history'][0];
	}

	$rawHeaders = "HTTP/1.1 200 OK" . 
		"\r\nDate: " . Carbon::now()->format('D, d M Y H:i:s T') .
		"\r\nContent-Type: text/html" . "\r\nLocation: " . $address;

	$response = new HttpResponse($rawHeaders, $rawHeaders, '', 200);

	return new Response($response);

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
 * @return Response
 */
function redirect($url=null, $code=302)
{
	if (isset($_POST)) $_SESSION['old'] = $_POST;
	
	$rawHeaders = "HTTP/1.1 $code OK" . 
		"\r\nDate: " . Carbon::now()->format('D, d M Y H:i:s T') .
		"\r\nContent-Type: text/html" . "\r\nLocation: ".$url;

	$response = new HttpResponse($rawHeaders, $rawHeaders, '', $code);

	return new Response($response);
}


/**
 * Returns a response with data\
 * Default response type is JSON\ 
 * Example: response($data, '200 OK')
 * 
 * @param mixed $data
 * @param string $code
 * @param array $headers
 * @return Response
 */
function response($data=null, $code=200, $headers=null)
{
	$type = 'text/html';
	
	if ($data instanceof Collection || $data instanceof Model) {
		$data = $data->toArray();
		$type = 'application/json';
	}

	if ($data instanceof FinalView){
		$data = $data->__toString();
	}

	if (is_array($data)) {
		$data = json_encode(collect($data)->toArray());
		$type = 'application/json';
	}

	if (!$headers) {
		$rawHeaders = "HTTP/1.1 " . $code . " " . HttpResponse::$reason_phrases[$code] . 
			"\r\nDate: " . Carbon::now()->format('D, d M Y H:i:s T') .
			"\r\nContent-Type: " . $type; // . "\r\ncontent-length:" . size($data);
	} else {
		$rawHeaders = "HTTP/1.1 " . $code . " " . HttpResponse::$reason_phrases[$code];
		foreach ($headers as $key => $val) {
			$rawHeaders .= $key . ': ' . $val . "\r\n";
		}
	}

	$response = new HttpResponse($rawHeaders . "\r\n" . $data, $rawHeaders, $data, $code);

	return new Response($response);
}

function base64url_encode($data) {
	return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
	return base64_decode(strtr($data, '-_', '+/'));
}

function ddd()
{
	__dump(func_get_args(), true, true);
}

function dd()
{
	__dump(func_get_args(), false, true);
}

function dump()
{
	__dump(func_get_args(), false, false);
}

function __dump($content, $full=false, $die=false)
{
	$theme = config('app.dumper_theme');

	$trace = debug_backtrace();

	$called_from = $trace[0];

	foreach ($trace as $tr) {
		if (!Str::contains($tr['file'], '/vendor/baradur')) {
			$called_from = $tr;
			break;
		}
	}

	$file = $called_from['file'];
	$line = $called_from['line'];

	$final_content = array();
	$show_buttons = false;

	//$dumper = new NewDumper;

	foreach ($content as $data) {

		/* ob_start();
		var_dump($data);
		$str = ob_get_contents();
		ob_end_clean();
	
		$matches = array();

		preg_match_all('/\)(#\d*)/', $str, $matches); */

		$res = VarDumper::getDump($data, $full/* , $matches[1] */);
		//$res = $dumper->process($data);

		if(strpos($res, '_btn">&#9660;</span')!==false) {
			$show_buttons = true;
		}

		$final_content[] = $res;
	}

	$legend = basename($file) . ': ' . $line;

	$html = '<div style="line-height:1.1rem;background:'.($theme=='dark'? '#18181b':'#f9fafb').
		';margin:0 0 .5rem 0;padding:.75rem 1rem .5rem;border:1px solid lavender;font-family:monospace;
		font-size:13px;color:'.($theme=='dark'? '#f9fafb':'#111827').
		';border: 1px solid'.($theme=='dark'? '#3f3f46':'#d1d5db').'">';

	if ($show_buttons) {
		$html .= '<style>.toggle_expand_button { background:'.($theme=='dark'? 'gray':'lightgray').
		';border:none;padding:5px 15px; } .toggle_expand_button:hover { background:darkgray; }</style>
		<div style="margin-bottom:.75rem;"><button class="toggle_expand_button" onclick="expandAll();">Expand all</button> 
		<button class="toggle_expand_button" onclick="collapseAll();">Collapse all</button></div>';
	}

	if ($theme=='light') {
		$final_content = str_replace('#56DB3A', '#629755', $final_content);
		$final_content = str_replace('#ff8400', '#CC7832', $final_content);
		$final_content = str_replace('#1299da', '#0e73a5', $final_content);
	}
	
	$html .= implode('<div style="margin:.5rem 0 0 0"></div>', $final_content).
		'<p style="color:gray;margin:.5rem 0 0 0;">// '.$legend.'</p></div>
		<script>function toggleDisplay(id) { 
			var current = document.getElementById(id).style.height;
			document.getElementById(id).style.height = (current == "auto") ? "0" : "auto"; 
			document.getElementById(id + "_btn").innerHTML = (current == "auto") ? "&#9654" : "&#9660"; 
			document.getElementById(id + "_close").style.display = (current == "auto") ? "inline" : "none"; 
			}
			function expandAll() {
				var elems = document.getElementsByName("expandable");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.height = "auto";
				}
				elems = document.getElementsByClassName("mybtn");
				for (var i = 0; i < elems.length; i++) {
					elems[i].innerHTML = "&#9660";
				}
				elems = document.getElementsByClassName("closing");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.display = "none";
				}
			}
			function collapseAll() {
				var elems = document.getElementsByName("expandable");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.height = 0;
				}
				elems = document.getElementsByClassName("mybtn");
				for (var i = 0; i < elems.length; i++) {
					elems[i].innerHTML = "&#9654";
				}
				elems = document.getElementsByClassName("closing");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.display = "inline";
				}
			}
		</script>';

	/* $html .= implode('<style> div .hidden { display:none; }</style>
		<div style="margin:.5rem 0 0 0"></div>', $final_content).
		'<p style="color:gray;margin:.5rem 0 0 0;">// '.$legend.'</p></div>
		<script>function toggleDisplay(id) { 
			var current = document.getElementById(id).style.height;
			document.getElementById(id).style.height = (current == "auto") ? "0" : "auto"; 
			document.getElementById(id + "_btn").innerHTML = (current == "auto") ? "&#9654" : "&#9660"; 
			document.getElementById(id + "_close").style.display = (current == "auto") ? "inline" : "none"; 
			document.getElementById(id + "_end").style.display = (current == "auto") ? "none" : "block"; 
			}
			function expandAll() {
				var elems = document.getElementsByName("expandable");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.height = "auto";
				}
				elems = document.getElementsByClassName("mybtn");
				for (var i = 0; i < elems.length; i++) {
					elems[i].innerHTML = "&#9660";
				}
				elems = document.getElementsByClassName("closing");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.display = "none";
				}
				elems = document.getElementsByClassName("ending");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.display = "block";
				}
			}
			function collapseAll() {
				var elems = document.getElementsByName("expandable");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.height = 0;
				}
				elems = document.getElementsByClassName("mybtn");
				for (var i = 0; i < elems.length; i++) {
					elems[i].innerHTML = "&#9654";
				}
				elems = document.getElementsByClassName("closing");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.display = "inline";
				}
				elems = document.getElementsByClassName("ending");
				for (var i = 0; i < elems.length; i++) {
					elems[i].style.display = "none";
				}
			}
		</script>'; */

	if ($die)
	{
		if (config('app.debug_info'))
		{
			global $debuginfo;
			$size = function_exists('memory_get_usage') ? memory_get_usage() : 0;
			//$peak = memory_get_peak_usage();
			$debuginfo['memory_usage'] = get_memory_converted($size);
			//$debuginfo['memory_peak'] = get_memory_converted($peak);
			//$params['debug_info'] = $debuginfo;
	
			$start = $debuginfo['start'];
			$end = microtime(true) - $start;
			$debuginfo['time'] = number_format($end, 2) ." seconds";
	
			$html .= "\n".'<script src="'.asset('assets/js/jquery-3.5.1.min.js') .'"></script>'."\n".
				'<script>var debug_info = '."[".json_encode($debuginfo)."]"."\n".
				'$(document).ready(function(e) {
					console.log("TIME: "+debug_info.map(a => a.time));
					console.log("MEMORY USAGE: "+debug_info.map(a => a.memory_usage));
					console.log("CACHE: "+debug_info.map(a => a.startup));
					//console.log("MEMORY PEAK: "+debug_info.map(a => a.memory_peak));
					let q = debug_info.map(a => a.queryes);
					if (q[0]) {
						q[0].forEach(function (item, index) {
						console.log("Query #"+(index+1));
						console.log(item);
						});
					}
				});</script>';
	
		}
		echo $html;

		__exit();
	}

	echo $html;

}

function csrf_field()
{
	return "<input type='hidden' name='_token' value='".csrf_token()."'/>\n";
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

function is_closure($closure)
{
	if (!is_array($closure)) {
		return false;
	}

	//return count(explode('|', $closure))>=4;
	//return count(explode('|', $closure))>=3;

	return count(explode('|', $closure[0]))==3;
}

function blank($value)
{
	if (is_null($value)) {
		return true;
	}

	if (is_string($value) && trim($value)=='') {
		return true;
	}

	if (is_array($value) && count($value)==0) {
		return true;
	}

	if ($value instanceof Collection) {
		return $value->count() == 0;
	}

	return false;		
}

function filled($value)
{
	return !blank($value);
}

function cache($value=null, $time=null)
{
	$cache = Cache::store();

	if (isset($value) && is_string($value)) {
		return $cache->get($value);
	}
	
	if (isset($value) && is_array($value)) {

		foreach ($value as $key => $val) {
			if ($cache instanceof FileStore) {
				$res = $cache->put($key, $val, $time? (int)$time : 60);
			} else {
				$res = $cache->put($key, $val);
			}
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

	if (!is_closure($callback)) {
		throw new InvalidArgumentException("Invalid callback for tap() method");
	}

	list($class, $method, $params) = getCallbackFromString($callback);
	$params[0] = $value;
	call_user_func_array(array($class, $method), $params);
	
	return $value;
}

function value($default, $parent=null)
{
	if (is_closure($default)) {
		list($class, $method, $params) = getCallbackFromString($default);
		$params[0] = $parent;
		return call_user_func_array(array($class, $method), $params);
	}

	return $default;
}

function optional($value, $callback=null)
{
	if (!$callback || !is_closure($callback)) {
		return new Optional($value);
	}
	elseif (!is_null($value)) {
		list($class, $method) = getCallbackFromString($callback);
		$value = call_user_func_array(array($class, $method), array($value));
		return $value;
	}
}

function with($value, $callback = null)
{
	if (!$callback || !is_closure($callback)) {
		return $value;
	}

	list($class, $method) = getCallbackFromString($callback);
	$value = call_user_func_array(array($class, $method), array($value));
	
	return $value;
}

function app_path($value=null)
{
	return _DIR_.'app/'.$value;
}

function is_assoc($arr)
{
	if (!is_array($arr)) {
		return false;
	}

    if (array() === $arr) {
		return false;
	}
	
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function data_fill(&$target, $key, $value)
{
	return data_set($target, $key, $value, false);
}

function data_get($target, $key, $default = null)
{
	if (is_null($key)) {
		return $target;
	}

	$key = is_array($key) ? $key : explode('.', $key);

	foreach ($key as $i => $segment) {
		unset($key[$i]);

		if (is_null($segment)) {
			return $target;
		}

		if ($segment === '*') {
			if ($target instanceof Collection) {
				$target = $target->all();
			} elseif (! is_assoc($target)) {
				return value($default);
			}

			$result = array();

			foreach ($target as $item) {
				$result[] = data_get($item, $key);
			}

			return in_array('*', $key) ? Arr::collapse($result) : $result;
		}

		if (Arr::accessible($target) && Arr::exists($target, $segment)) {
			$target = $target[$segment];
		} elseif ($target instanceof Model && $target->getAttribute($segment)) {
			$target = $target->{$segment};
		} elseif (is_object($target) && isset($target->{$segment})) {
			$target = $target->{$segment};
		} else {
			return value($default);
		}
	}

	return $target;
}


function data_set(&$target, $key, $value, $overwrite = true)
{
	$segments = is_array($key) ? $key : explode('.', $key);

	if (($segment = array_shift($segments)) === '*') {
		if (! Arr::accessible($target)) {
			$target = array();
		}

		if ($segments) {
			foreach ($target as &$inner) {
				data_set($inner, $segments, $value, $overwrite);
			}
		} elseif ($overwrite) {
			foreach ($target as &$inner) {
				$inner = $value;
			}
		}
	} elseif (Arr::accessible($target)) {
		if ($segments) {
			if (! Arr::exists($target, $segment)) {
				$target[$segment] = array();
			}

			data_set($target[$segment], $segments, $value, $overwrite);
		} elseif ($overwrite || ! Arr::exists($target, $segment)) {
			$target[$segment] = $value;
		}
	} elseif (is_object($target)) {
		if ($segments) {
			if (! isset($target->{$segment})) {
				$target->{$segment} = array();
			}

			data_set($target->{$segment}, $segments, $value, $overwrite);
		} elseif ($overwrite || ! isset($target->{$segment})) {
			$target->{$segment} = $value;
		}
	} else {
		$target = array();

		if ($segments) {
			data_set($target[$segment], $segments, $value, $overwrite);
		} elseif ($overwrite) {
			$target[$segment] = $value;
		}
	}

	return $target;
}

function data_forget(&$target, $key)
{
	$segments = is_array($key) ? $key : explode('.', $key);

	if (($segment = array_shift($segments)) === '*' && Arr::accessible($target)) {
		if ($segments) {
			foreach ($target as &$inner) {
				data_forget($inner, $segments);
			}
		}
	} elseif (Arr::accessible($target)) {
		if ($segments && Arr::exists($target, $segment)) {
			data_forget($target[$segment], $segments);
		} else {
			Arr::forget($target, $segment);
		}
	} elseif (is_object($target)) {
		if ($segments && isset($target->{$segment})) {
			data_forget($target->{$segment}, $segments);
		} elseif (isset($target->{$segment})) {
			unset($target->{$segment});
		}
	}

	return $target;
}

function head($array)
{
	return reset($array);
}

function last($array)
{
	return end($array);
}

function vite_assets($asset=null)
{
	$asset = $asset? $asset : '/resources/js/app.js';
	$base = basename($asset);

	if (config('app.env')=='local')
	{

		$address = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['REMOTE_ADDR'] . ':3000/resources/js/app.js';

		if(Http::retry(1)->get($address)->error_code==null)
		{
			return '<script type="module" src="http://localhost:3000/@vite/client"></script>
				<script type="module" src="http://localhost:3000/' . $asset . '"></script>';
		}
		
		//return '<script type="module" src="http://localhost:3000/@vite/client"></script>
		//	<script type="module" src="http://localhost:3000/resources/js/app.js"></script>';
	}

	//$manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

	//return '<script type="module" src="/build/'. $manifest['resources/js/app.js']['file'] . '"></script>';

	return '<script type="module" src="' . asset('assets/' . $base) . '"></script>';

}

function mime_type($filename)
{
	$mime_types = array(
		'txt' => 'text/plain',
		'htm' => 'text/html',
		'html' => 'text/html',
		'css' => 'text/css',
		'json' => array('application/json', 'text/json'),
		'xml' => 'application/xml',
		'swf' => 'application/x-shockwave-flash',
		'flv' => 'video/x-flv',

		'hqx' => 'application/mac-binhex40',
		'cpt' => 'application/mac-compactpro',
		'csv' => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'),
		'bin' => 'application/macbinary',
		'dms' => 'application/octet-stream',
		'lha' => 'application/octet-stream',
		'lzh' => 'application/octet-stream',
		'exe' => array('application/octet-stream', 'application/x-msdownload'),
		'class' => 'application/octet-stream',
		'so' => 'application/octet-stream',
		'sea' => 'application/octet-stream',
		'dll' => 'application/octet-stream',
		'oda' => 'application/oda',
		'ps' => 'application/postscript',
		'smi' => 'application/smil',
		'smil' => 'application/smil',
		'mif' => 'application/vnd.mif',
		'wbxml' => 'application/wbxml',
		'wmlc' => 'application/wmlc',
		'dcr' => 'application/x-director',
		'dir' => 'application/x-director',
		'dxr' => 'application/x-director',
		'dvi' => 'application/x-dvi',
		'gtar' => 'application/x-gtar',
		'gz' => 'application/x-gzip',
		'php' => 'application/x-httpd-php',
		'php4' => 'application/x-httpd-php',
		'php3' => 'application/x-httpd-php',
		'phtml' => 'application/x-httpd-php',
		'phps' => 'application/x-httpd-php-source',
		'js' => array('application/javascript', 'application/x-javascript'),
		'sit' => 'application/x-stuffit',
		'tar' => 'application/x-tar',
		'tgz' => array('application/x-tar', 'application/x-gzip-compressed'),
		'xhtml' => 'application/xhtml+xml',
		'xht' => 'application/xhtml+xml',             
		'bmp' => array('image/bmp', 'image/x-windows-bmp'),
		'gif' => 'image/gif',
		'jpeg' => array('image/jpeg', 'image/pjpeg'),
		'jpg' => array('image/jpeg', 'image/pjpeg'),
		'jpe' => array('image/jpeg', 'image/pjpeg'),
		'png' => array('image/png', 'image/x-png'),
		'tiff' => 'image/tiff',
		'tif' => 'image/tiff',
		'shtml' => 'text/html',
		'text' => 'text/plain',
		'log' => array('text/plain', 'text/x-log'),
		'rtx' => 'text/richtext',
		'rtf' => 'text/rtf',
		'xsl' => 'text/xml',
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'word' => array('application/msword', 'application/octet-stream'),
		'xl' => 'application/excel',
		'eml' => 'message/rfc822',
		'ico' => 'image/vnd.microsoft.icon',
		'svg' => 'image/svg+xml',
		'svgz' => 'image/svg+xml',
		'zip' => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'),
		'rar' => 'application/x-rar-compressed',
		'msi' => 'application/x-msdownload',
		'cab' => 'application/vnd.ms-cab-compressed',
		'mid' => 'audio/midi',
		'midi' => 'audio/midi',
		'mpga' => 'audio/mpeg',
		'mp2' => 'audio/mpeg',
		'mp3' => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'),
		'aif' => 'audio/x-aiff',
		'aiff' => 'audio/x-aiff',
		'aifc' => 'audio/x-aiff',
		'ram' => 'audio/x-pn-realaudio',
		'rm' => 'audio/x-pn-realaudio',
		'rpm' => 'audio/x-pn-realaudio-plugin',
		'ra' => 'audio/x-realaudio',
		'rv' => 'video/vnd.rn-realvideo',
		'wav' => array('audio/x-wav', 'audio/wave', 'audio/wav'),
		'mpeg' => 'video/mpeg',
		'mpg' => 'video/mpeg',
		'mpe' => 'video/mpeg',
		'qt' => 'video/quicktime',
		'mov' => 'video/quicktime',
		'avi' => 'video/x-msvideo',
		'movie' => 'video/x-sgi-movie',
		'pdf' => 'application/pdf',
		'psd' => array('image/vnd.adobe.photoshop', 'application/x-photoshop'),
		'ai' => 'application/postscript',
		'eps' => 'application/postscript',
		'doc' => 'application/msword',
		'xls' => array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'),
		'ppt' => array('application/powerpoint', 'application/vnd.ms-powerpoint'),
		'odt' => 'application/vnd.oasis.opendocument.text',
		'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
	);
  
	$ext = explode('.', $filename);
	$ext = strtolower(end($ext));
   
	if (array_key_exists($ext, $mime_types)) {
	  return (is_array($mime_types[$ext])) ? $mime_types[$ext][0] : $mime_types[$ext];
	} else if (function_exists('finfo_open')) {
	   if(file_exists($filename)) {
		 $finfo = finfo_open(FILEINFO_MIME);
		 $mimetype = finfo_file($finfo, $filename);
		 finfo_close($finfo);
		 return $mimetype;
	   }
	}
   
	return 'application/octet-stream';
}

function array_sort($array, $on, $order=SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

		//ddd($sortable_array);

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
            break;
            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function getCallbackFromString($string)
{
	$closurestring = str_replace(', ', ',', $string[0]);
	array_shift($string);

	//preg_match('/(.+?(?=\|))\|(.+?(?=\|))\|(.+?(?=\|))\|(.*)/', $string, $matches);
	preg_match('/(.+?(?=\|))\|(.+?(?=\|))\|(\d)/', $closurestring, $matches);

	$class = $matches[1];
	$method = $matches[2];
	$params = $string; //explode(',', $matches[4]);
	$use_params = $matches[3];

	$reflectionMethod = new ReflectionMethod($class, $method);
    $paramNames = $reflectionMethod->getParameters();

	$names = array();
	foreach ($paramNames as $param) {
		$names[] = $param->getName();
	}

	$result = array($class, $method, $params, $names);

    return $result;
}

function executeCallback($class, $method, $parameters, $parent=null, $construct=true)
{
	//dd(func_get_args());

	if (is_string($class) && !class_exists($class, false) && (file_exists(_DIR_.'storage/framework/classes/'.$class.'.php'))) {
		require_once(_DIR_.'storage/framework/classes/'.$class.'.php');
	}

    $reflectionMethod = new ReflectionMethod($class, $method);
    $paramNames = $reflectionMethod->getParameters();

    if (is_string($class) && $construct) {
        $class = new $class;
	} else {
		$class = $parent;
	}

	//dump($paramNames);

	//dump(get_class($class). " :: ".$method); dump($paramNames);

	# Class is already cloned for Builder, Collection returns empty array
    # I don't remember why I did this
    /* if (($class instanceof Builder || $class instanceof Collection) && $parent)
    {
        $test = $parent->__paramsToArray();
        
        foreach ($test as $key => $val)
        {
            $property = new \ReflectionProperty(get_class($class), $key);
            $property->setAccessible(true);
            $property->setValue($class, $val);
        }
    } */
    
    $fp = array();
    
    for ($i=0; $i<count($paramNames); $i++) {

		$paramClass = $paramNames[$i]->getClass()!=null
			? $paramNames[$i]->getClass()->getName() 
			: null;

        if (isset($parameters[$i])) {

			if ($parameters[$i] instanceof Exception) {				
				if (get_class($parameters[$i])==$paramClass) {
					$fp[] = $parameters[$i];
				} else {
					return; 
				}
			} else {
				$fp[] = $parameters[$i];
			} 
        
        } else {
            
            $fp[] = $paramNames[$i]->isDefaultValueAvailable() 
                ? $paramNames[$i]->getDefaultValue()
                : null;
        }
    }

    return $reflectionMethod->invokeArgs($class, $fp);

}

function find_similar($input, $words, $limit=4)
{
	$shortest = -1;
	$closest = '';
	// loop through words to find the closest
	foreach ($words as $word) {

		// calculate the distance between the input word,
		// and the current word
		$lev = levenshtein($input, $word);

		// check for an exact match
		if ($lev == 0) {

			// closest word is this one (exact match)
			$closest = $word;
			$shortest = 0;

			// break out of the loop; we've found an exact match
			break;
		}

		// if this distance is less than the next found shortest
		// distance, OR if a next shortest word has not yet been found
		if ($lev <= $shortest || $shortest < 0) {
			// set the closest match, and shortest distance
			$closest  = $word;
			$shortest = $lev;
		}
	}

	return $shortest < $limit ? $closest : null;
}

function __match($value, $options)
{
	foreach ($options as $key => $val) {
		if ($key == $value) {
			return $val;
		}
	}
	return $options['default'];
}

function event($event)
{
	return app('Dispatcher')->dispatch($event);
}


