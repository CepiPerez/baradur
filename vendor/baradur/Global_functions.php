<?php

function asset($val) { return View::getAsset($val); }
function route() { return Route::getRoute(func_get_args()); }
function session($val) { return App::getSession($val); }
function request() { return Helpers::getRequest(); }
function __($translation, $placeholder=null) { return Helpers::trans($translation, $placeholder); }
function public_path($path=null) { return env('APP_URL').'/'.env('PUBLIC_FOLDER').'/'.$path; }
function storage_path($path=null) { return _DIR_.'/storage/'.$path; }
function csrf_token() { return App::generateToken(); }

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
	global $app, $temp_params;
	$app->action = 'show';
	if (!isset($params) && isset($temp_params)) $params = $temp_params;
	$app->result = View::loadTemplate($template, $params);
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
	if ($error==403)
		$errormsg = "You don't have permission to access";
	else if ($error==404)
		$errormsg = "Resource not found on this server";
	error($error, $errormsg);
}

/**
 * Shows the error page with custom number and message\
 * Example: error(666, 'Unexpected error')
 * 
 * @param string $error
 * @param string $message
 */
function error($error, $message)
{
	$breadcrumb = array(__('login.home') => '', 'Error' => '#');
	echo View::loadTemplate('layouts/error', compact('error', 'message', 'breadcrumb'));
	exit();
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

	$app->action = 'response';
	$app->type = $type;
	$app->code = $code;
	$app->filename = $filename;
	$app->inline = $inline;
	$app->result = $data;
	$app->headers = $headers;

	return $app;
}

/**
 * Prints data (like var_dump) with formatting
 * 
 * @param string $data
 */
function dd($data)
{
	highlight_string("<?php\n" . print_r($data, true) . ";?>");
	echo "<br>";
}

function csrf_field()
{
	return "<input type='hidden' name='csrf' value='".csrf_token()."'/>\n";
}

function method_field($v)
{
	return "<input type='hidden' name='_method' value='$v'/>\n";
}
