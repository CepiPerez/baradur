<?php

$_closures = array();
$_currentClosureFile = null;

function callbackReplaceClosures($match)
{
	global $_closures, $_currentClosureFile;


	$method = 'closure_'.count($_closures);
	$closure = $match[0];

	//$pattern = '/function[\s]*[\S]*.*?\)/x';
    $pattern = '/function[\s]*[\S](.*?)\)(:?.*use[\s]*\(([^\)]*)\))?/x';
	preg_match($pattern, $match[0], $matches);

    $parameters = array();

    if (count($matches)>1 && strlen(trim($matches[1]))>0) $parameters = array_merge($parameters, explode(',', $matches[1]));
    if (count($matches)>3) $parameters = array_merge($parameters, explode(',', $matches[3]));

	$methodname = $method .'('. implode(', ', $parameters) .')';

    $closure = str_replace( $matches[0], $methodname, $match[0]);

	$_closures[] = $closure;

	//dd($method);
    $callback = 'baradurClosures';
    if (isset($_currentClosureFile))
        $callback .= '_'.$_currentClosureFile;
			
	return ", '$callback@".$method."')";

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

function callbackReplaceGroupClosuresInProvider($match)
{
	$closure = $match[0];

	$pattern = '/function[\s]*[\S]*\([\s]*[\S]*\)[\s]*[\S]*{(.*[\s\S]*?);[\s\]*}[\s]*\)/x';
	preg_match_all($pattern, $closure, $matches);
	return "->routes(". str_replace(';', ',', $matches[1][0]) . "\n)";
}

function processRoutes($path, $file)
{
    $routeFile = file_get_contents($path.$file);
    $classname = str_replace('.php', '', str_replace('.PHP', '', basename($file)));

    # Closures
    global $_currentClosureFile;
    $_currentClosureFile = $classname;
    $pattern = '/,[\s]*[\S]*function[\s\S]*?}[\s]*\)/x';
    $routeFile = preg_replace_callback($pattern, 'callbackReplaceClosures', $routeFile);
    $_currentClosureFile = null;

    # Group closures
    $pattern = '/->group[\s]*[\S]*\([\s]*[\S]*function[\s\S]*?}[\s]*\)/x';
    $routeFile = preg_replace_callback($pattern, 'callbackReplaceGroupClosures', $routeFile);

    $routeFile = replaceNewPHPFunctions($routeFile);

    global $_closures;

    if (count($_closures)>0)
    {
        $controller = "<?php\n\nclass baradurClosures_$classname {\n\n";
        foreach ($_closures as $closure)
        {
            $closure = rtrim( ltrim($closure, ","), ")");
            $controller .= "\tpublic function ".$closure."\n\n";
        }
        $controller .= "}";

        Cache::store('file')->setDirectory($path.'/storage/framework/cache/classes')
            ->plainPut($path.'/storage/framework/cache/classes/baradurClosures_'.basename($file), $controller);

        include($path.'/storage/framework/cache/classes/baradurClosures_'.basename($file));

    }
    
    Cache::store('file')->setDirectory($path.'/storage/framework/cache/classes')
        ->plainPut($path.'/storage/framework/cache/classes/baradurRoutes_'.basename($file), $routeFile);
    
    return include($path.'/storage/framework/cache/classes/baradurRoutes_'.basename($file));


}

function callbackReplaceArrayStart($match)
{
    return $match[1] . 'array(';

}

function callbackReplaceArrayEnd($match)
{
    return $match[1] . str_replace(']', ')', $match[2]);
}

function callbackReplaceRealArray($match)
{
    return str_replace('[', '_arrayStart_', str_replace(']', '_arrayEnd_', $match[1]));
}

static $_baradur_current_arrow_func = 0;

function callbackReplaceArrayFunction($match)
{
    global $_baradur_current_arrow_func;
    $type = substr(trim($match[0]), 0, 3);
    
    if ($type!='get' && $type!='set')
    {
        $type = $_baradur_current_arrow_func==0? 'get' : 'set'; 
    }
    ++$_baradur_current_arrow_func;

    return "    '$type' => ";

}

function callbackReplaceAccessors($match)
{
    global $_baradur_current_arrow_func;
    $_baradur_current_arrow_func = 0;

    $match2 = preg_replace_callback('/(?:[g|s]et\s*)?.*[^⁼>*]=>/x','callbackReplaceArrayFunction', $match[3]);
    $match2 = preg_replace('/(return)[\s]*([^\(]*)/x', "return array", $match2);
    $match2 = preg_replace('/,([\s]*\))/x', '$1', $match2);
    return 'public function '. $match[2] . 'Attribute($value, $attributes) {' . $match2 . "}\n";
}


function replaceNewPHPFunctions($text)
{
    $text = preg_replace('/(=[\s]*)(\[\])/x', '$1 array()', $text);
    $text = str_replace('=[', '= [', str_replace('([', '( [', str_replace(' [', '  [', $text)));

    $text = preg_replace_callback('/(\w\[[^,|;|)]*)/x', 'callbackReplaceRealArray', $text);

    # New array method -> []
    $text = preg_replace_callback('/([\W][^\]])\[/x', 'callbackReplaceArrayStart', $text);
    //$text = preg_replace_callback('/(array\([^\]]*)(\]*[\W]*\])/x', 'callbackReplaceArrayEnd', $text);
    $text = str_replace(']', ')', $text);
    $text = str_replace('_arrayStart_', '[', $text);
    $text = str_replace('_arrayEnd_', ']', $text);


    # something ?? else  -> isset(something)? something : else
    //$text = preg_replace('/\(([\w|\$])/x', "( $1", $text);
    //$text = preg_replace('/[\s]([^\s]*.?[^\b.*[^\?{2}])(\?{2})/x', "isset($1) ? $1 : ", $text);
    $text = preg_replace('/\s([^\s]*.?[^\b.*[^\?{2}])(\?{2})/x', " isset($1) ? $1 : ", $text);

    # Someclass::class to 'Someclass' and \Path\To\SomeClass::class to 'SomeClass"
    //$text = str_replace('::class', '', preg_replace('/\w*::class/x', "'$0'", $text));
    $text = str_replace('::class', '', preg_replace('/(?:[\\\|\w].*?[$\\\])?(\w*)(::class)/x', "'$1'", $text));

    # New accessors and mutators: Arrow functions
    $text = preg_replace_callback('/(\w*)[\s]*function[\s]*(\w*)\(\)\s*:\s*Attribute[^{]*{([^}]*)}/x', 'callbackReplaceAccessors', $text);

    return $text;

}