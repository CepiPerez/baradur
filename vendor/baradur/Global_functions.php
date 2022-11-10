<?php

function app($val=null) { global $app; return $app->instance($val); }
function asset($val) { return View::getAsset($val); }
function route() { return Route::getRoute(func_get_args()); }
function session($val) { return App::getSession($val); }
function request() { return app('request'); }
function __($translation, $placeholder=null) { return Helpers::trans($translation, $placeholder); }
function public_path($path=null) { return env('APP_URL').'/'.env('PUBLIC_FOLDER').'/'.$path; }
function storage_path($path=null) { return _DIR_.'/storage/'.$path; }
function base_path($path=null) { return _DIR_.'/../../'.$path; }
function csrf_token() { return App::generateToken(); }
function config($val) { return Helpers::config($val); }
function to_route($route) { return redirect()->route($route); }
function class_basename($name) { return get_class($name); }
function auth() { return new Auth; }
function abort_if($condition, $code) { if ($condition) abort(404); }
function abort_unless($condition, $code) { if (!$condition) abort(404); }
function str($string=null) { if (!$string) return new Str; else return Str::of($string); }
function fake() { return new Faker; }

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
	if ($error==403)
		$errormsg = __("You don't have permission to access this resource");
	else if ($error==404)
		$errormsg = __("Resource not found on this server");
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
	#header('Access-Control-Allow-Origin: http://localhost');
	#header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
	#header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
	//echo View::loadTemplate('layouts.error', compact('error_code', 'error_message', 'breadcrumb'));
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
	if (is_array($data))
	{
		$final = array();
		foreach ($data as $key => $val)
		{
			/* if (is_object($val) && method_exists($val, 'getQuery'))
			{
				$col = new Collection(get_class($val), $val->getQuery()->_hidden);
				$val = $col->collect($val, get_class($val))->toArray();
			} */

			$final[$key] = $val;
		}
		$data = $final;
	}
	elseif (is_object($data) && get_class($data)=='Collection')
	{
		$data = $data->toArray();
	}
	elseif (is_object($data))
	{
		$final = array();
		if (method_exists($data, 'getQuery'))
		{
			$col = new Collection(get_class($data), $data->getQuery()->_hidden);
			$val = $col->collect($data, get_class($data))->toArray();
		}
		$final[] = $val;
		$data = $final[0];
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
/* function u_print_r($full, $subject, $ignore = array(), $depth = 1, $refChain = array())
{
	global $_model_list, $__currentArray;
	$res = '';

	$colors = array(1=>'blue', 2=>'green', 3=>'darkslategray', 4=>'slateblue', 5=>'gray', 
		6=>'teal', 7=>'cadetblue');

	//return "$matches[1]<a href=\"javascript:toggleDisplay('$id');\">$matches[2]</a><div id='$id' style=\"display: none;\">"

    if ($depth > 20) return;

    if (is_object($subject)) 
	{
        foreach ($refChain as $refVal)
            if ($refVal === $subject) {
                $res .= "*RECURSION*<br>";
                return;
            }
        array_push($refChain, $subject);

		$id = substr(md5(rand().get_class($subject)), 0, 7);

        $res .= '<a onclick="toggleDisplay(\''.$id.'\');" style="cursor:pointer;">
			<span style="color:'.(in_array(get_class($subject), $_model_list)?'navy':'royalblue'). ';">'.get_class($subject) .'</span>
			<span style="color:orange;font-size:.8rem;"> &lt;'.
			(in_array(get_class($subject), $_model_list)? 'Model' :
			(get_class($subject)=='Collection'? 'Collection' : 'Object')) .'&gt; </span>
			</a><button onclick="toggleDisplay(\''.$id.'\');" name="'.$id.'" class="btn" style="padding:0 .2rem;margin:0.5rem 0 0rem 0;font-size:.6rem;">+</button>
			<div id="'.$id.'" name="expandable" style="height:'.($depth==1?'auto':'0').';overflow:hidden;">
			';

		if (get_class($subject)=='Collection')
		{
			if ($subject->pagination)
			$res .= "<i style='margin-left:".($depth * 2)."rem;color:gray;'>&lt;has pagination&gt;</i><br>";
		}
		
        $subject = (array) $subject;
        foreach ($subject as $key => $val)
			if ($key{0} != "\0" || $full) {
				if (is_array($ignore) && !in_array($key, $ignore, 1)) {
					$res .= "
					<span style='margin-left:".($depth * 2)."rem;color:";
					$res .= $colors[$depth] .";'> [";
					if ($key{0} == "\0") {
						$keyParts = explode("\0", $key);
						$res .= $keyParts[2] . '<span style="color:red;font-size:.85rem;">';
						$res .= (($keyParts[1] == '*')  ? ':protected' : ':private') . '</span>';
					} else {
						$res .= $key;
					}
					$res .= ']</span><span style="color:gray;font-size:.85rem;"> => </span>';
					$res .= u_print_r($full, $val, $ignore, $depth + 1, $refChain);
				}
			}

        if (substr($res, -4)=="<br>")
			$res = substr($res, 0, -4);

		$res .= "</div>";
        array_pop($refChain);
    } 
	elseif (is_array($subject)) 
	{
		$id = substr(md5(rand().'Array'.$__currentArray), 0, 7);
		$__currentArray++;

		//<button onclick="toggleDisplay(\''.$id.'\');" style="padding:0 .2rem;margin:0.5rem 0 0rem 0;font-size:.6rem;">+</button>

        $res .= '<a onclick="toggleDisplay(\''.$id.'\');" style="cursor:pointer;">
			</span><span style="color:coral;"> Array</span>
			</a><button onclick="toggleDisplay(\''.$id.'\');" name="'.$id.'" class="btn" style="padding:0 .2rem;margin:0.5rem 0 0rem 0;font-size:.6rem;">+</button>
			<div id="'.$id.'" name="expandable" style="height:'.($depth==1?'auto':'0').';overflow:hidden;">';
        foreach ($subject as $key => $val)
            if (is_array($ignore) && !in_array($key, $ignore, 1)) {
                $res .= "<span style='margin-left:".($depth * 2)."rem;color:";
				$res .= $colors[$depth] .";'> [" . $key . ']';
				$res .=	'<span style="color:gray;font-size:.85rem;"> => </span>';
                $res .= u_print_r($full, $val, $ignore, $depth + 1, $refChain);
            }

		if (substr($res, -4)=="<br>")
			$res = substr($res, 0, -4);	
		
		$res .= "</div>";
        //$res .= "<br>";
    } else
	{
        $res .=  $subject . "<br>";
	}

	return $res;
} */

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
	
	//$res = u_print_r($full, $data); // u_print_r($usebr, $full, $data);
	global $_model_list;
	$res = PrettyDump::getDump($data, $full, array('Model' => $_model_list, 'Collection'=> 'Collection'));

	//$res = str_replace("<br><span style='margin-left:4rem;'>", "<span style='margin-left:4rem;'>", $res);
	//$res = str_replace('<br></div><br>', '</div><br>', $res);
	//$res = str_replace('<br></div></div><br>', '</div></div><br>', $res);
	//while (strpos($res, '<br><br>')) 
	//	$res = str_replace("<br><br>", "<br>", $res);
	
	/* if(strpos($res, '<button')!==false)
	{
		$res = '<button onclick="expandAll();">Expand all</button> 
		<button onclick="collapseAll();">Collapse all</button><br><br>'.$res;

	} */

	/* echo('<style>* {'.($die? 'font-family:monospace;font-size:13px;' : '') .'margin:0;}</style>
	<div style="line-height:1.4rem;background:ghostwhite;margin:.5rem;padding:1rem;
	border:1px solid lavender;">'.$res.'</div>
	<script>function toggleDisplay(id) { 
		document.getElementById(id).style.height = (document.getElementById(id).style.height == "auto") ? "0" : "auto"; 
		document.getElementsByName(id)[0].innerHTML = (document.getElementsByName(id)[0].innerHTML == "+") ? "-" : "+"; 
		}
		function expandAll() {
			var elems = document.getElementsByName("expandable");
			for (var i = 0; i < elems.length; i++) {
				elems[i].style.height = "auto";
			}
			elems = document.getElementsByClassName("btn");
			for (var i = 0; i < elems.length; i++) {
				elems[i].innerHTML = "-";
			}
		}
		function collapseAll() {
			var elems = document.getElementsByName("expandable");
			for (var i = 0; i < elems.length; i++) {
				elems[i].style.height = 0;
			}
			elems = document.getElementsByClassName("btn");
			for (var i = 0; i < elems.length; i++) {
				elems[i].innerHTML = "+";
			}
		}
	</script>'); */

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

function collect($data)
{
	$collection = new Collection('stdClass');
	$collection->collect($data);
	return $collection;
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