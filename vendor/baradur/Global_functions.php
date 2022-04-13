<?php

function asset($val) { return View::getAsset($val); }
function route($val) { return Route::getRoute(func_get_args()); }
function session($val) { return App::getSession($val); }
function request() { return Helpers::getRequest(); }
function __($translation, $placeholder=null) { return App::trans($translation, $placeholder); }

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


/**
 * Returns the template\
 * Example: view('products', compact())
 * 
 * @param string $template Template file (without .blade.php)
 * @param string $params Parameters to send to template 
 * @return App
 */
function view($template, $params=array())
{
	global $app;
	$app->action = 'show';
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
		$errormsg = "Usted no tiene autorizacion para ingresar a la pagina solicitada";
	else if ($error==404)
		$errormsg = "No se encontro la pagina solicitada";
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
	$breadcrumb = array(__('login.home') => HOME, 'Error' => '#');
	echo View::loadTemplate('common/error', compact('error', 'message', 'breadcrumb'));
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
	if ($url) $app->result = HOME .'/'. ltrim($url, '/');
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
function response($data, $code, $type='json', $filename=null)
{
	global $app;

	$app->action = 'response';
	$app->type = $type;
	$app->code = $code;
	$app->filename = $filename;
	$app->result = $data;

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