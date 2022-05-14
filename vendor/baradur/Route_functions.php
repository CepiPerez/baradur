<?php

$_closures = array();
function callbackReplaceClosures($match)
{
	global $_closures;
	$method = 'closure_'.count($_closures);
	$closure = $match[0];

	$pattern = '/function[\s]*[\S]*.*?\)/x';
	preg_match($pattern, $match[0], $matches);

	$methodname = str_replace('function', $method, $matches[0]);
	$closure = str_replace( $matches[0], $methodname, $match[0]);

	$_closures[] = $closure;

	//dd($method);
			
	return ", 'baradurClosures@".$method."')";

}

function callbackReplaceGroupClosures($match)
{
	$closure = $match[0];

	//dd($match[0]);

	$pattern = '/function[\s]*[\S]*\([\s]*[\S]*\)[\s]*[\S]*{(.*[\s\S]*?);[\s\]*}[\s]*\)/x';
	preg_match_all($pattern, $closure, $matches);

	//dd($matches[1][0]);

	//$closure = str_replace('}', '', str_replace(';', ',', $closure));
			
	//dd($closure);
	return "->group(". str_replace(';', ',', $matches[1][0]) . "\n)";
}

function processRoutes($path, $file)
{
	$routeFile = file_get_contents($path.$file);

    $pattern = '/,[\s]*[\S]*function[\s\S]*?}[\s]*\)/x';
    $routeFile = preg_replace_callback($pattern, 'callbackReplaceClosures', $routeFile);

    $pattern = '/->group[\s]*[\S]*\([\s]*[\S]*function[\s\S]*?}[\s]*\)/x';
    $routeFile = preg_replace_callback($pattern, 'callbackReplaceGroupClosures', $routeFile);

    $routeFile = preg_replace_callback('/([\W][^\]])\[/x', 'callbackReplaceArrayStart', $routeFile);
    $routeFile = preg_replace_callback('/(array\([^\]]*)(\]*[\W]*\])/x', 'callbackReplaceArrayEnd', $routeFile);


    $routeFile = str_replace('::class', '', preg_replace('/\w*::class/x', "'$0'", $routeFile));

    //$routeFile = preg_replace('/\[.*?([^\]]*).?]/x', 'array($1)', $routeFile);

    global $_closures;

    if (count($_closures)>0)
    {
        $controller = "<?php\n\nclass baradurClosures {\n\n";
        foreach ($_closures as $closure)
        {
            $closure = rtrim( ltrim($closure, ","), ")");
            $controller .= "\tpublic function ".$closure."\n\n";
        }
        $controller .= "}";


       /*  unlink($path.'/storage/framework/cache/classes/baradurClosures.php');
        unlink($path.'/storage/framework/cache/classes/baradurRoutes.php');
        file_put_contents($path.'/storage/framework/cache/classes/baradurClosures.php', $controller);
        file_put_contents($path.'/storage/framework/cache/classes/baradurRoutes.php', $routeFile); */

        Cache::store('file')->setDirectory(_DIR_.'/storage/framework/cache/classes')
            ->plainPut($path.'/storage/framework/cache/classes/baradurClosures.php', $controller);

        Cache::store('file')->setDirectory(_DIR_.'/storage/framework/cache/classes')
            ->plainPut($path.'/storage/framework/cache/classes/baradurRoutes.php', $routeFile);
        
        include($path.'/storage/framework/cache/classes/baradurClosures.php');
        include($path.'/storage/framework/cache/classes/baradurRoutes.php');

        //dd($controller);
    }
}

function callbackReplaceArrayStart($match)
{
    return $match[1] . 'array(';

}

function callbackReplaceArrayEnd($match)
{
    return $match[1] . str_replace(']', ')', $match[2]);
}