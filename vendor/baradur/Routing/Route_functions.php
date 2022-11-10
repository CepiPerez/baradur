<?php

$_closures = array();
$_currentClosureFile = null;
$_arrow_functions = array();
$_current_classname = array();
$_functions_to_add = array();
$_trait_to_add = '';

/* function loadModels($path)
{
    global $_model_list;

    $it = new RecursiveDirectoryIterator($path.'/app/models');
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
        if (substr($file,-4) =='.php' || substr($file,-4) == '.PHP')
        {
            $_model_list[] = str_replace('.php', '', str_replace('.PHP', '', basename($file)));
        }
    }
    unset($it);
} */

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

function callbackReplaceQueryes($match)
{
    //dump($match);

    global $_arrow_functions, $_current_classname;

    if (count($_current_classname)==0)
        return $match[0];

    $res = $match[2]; //$match['query'];
    //dump($res);

    //$res = preg_replace_callback('/(?<sign>[=|>|,|\(])[\s]*function[\s]*[\S](?<param>.*?)\)[\s]*(?<main>[^\{\}]*){0}(?<query>\{\g<main>\}|\{(?:\g<main>\g<query>\g<main>)*\})/x', 'callbackReplaceQueryes', $res);
    $res = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceQueryes', $res);

    preg_match('/function[\s]*[\S](.*?)\)(:?.*use[\s]*\(([^\)]*)\))?/x', $match[0], $matches);
    //dump($res);dump($matches);
    $params = array();
    //$params[] = "'".$match[2]."'";
    $default = $matches[1]; //str_replace('$', '', $matches[1]);
    //dump("DEFAULT: ".$default);

    if (count($matches)>3)
    {
        $replace = trim($matches[2]);
        $res = str_replace($replace, '', $res);
        foreach (explode(',', $matches[3]) as $m)
            $params[] = trim($m);
    }

    $defparms = array();
    if ($default)
    {
        foreach (explode(',', $default) as $def)
        {
            $temp = trim($def);
            if ($temp) {
                if (strpos($temp, ' ')!==false)
                {
                    $arr = explode(' ', $temp);
                    $temp = $arr[1];
                }
                $defparms[] = $temp.count($_arrow_functions);
            }
                
        }
    }
    /* if ($default!='')
        $default .= count($_arrow_functions); */
    
    $return = '"baradurClosures_'.$_current_classname[0].'@closure_'.count($_arrow_functions)."(".
        implode(', ', array_merge($defparms, $params)) . ')"';

    //dump($return);

    $res = ltrim(trim($res), '{');
    $res = rtrim(trim($res), '}');

    $method  = 'public static function closure_'.count($_arrow_functions). "(".($default/* !=''?'$'.$default:'' */).(count($params)>0? ($default!=''?', ':'').implode(', ',$params):'').") {\n";
    $method .= /* $match[2]." = Model::instance('DB');\n". */$res."\n}\n";

    $_arrow_functions[] = $method;

    $temp = $match[1];
    $temp = str_replace('function', '', $temp);

	return $temp.' '.$return; // $match['sign'].' '.$return;

}

function callbackReplaceNewArray($match)
{
    //$res = $match['query'];
    //$res = preg_replace_callback('/(?<sign>[\s|\(|,|=])(?<main>[^\[\]]*){0}(?<query>\[\g<main>\]|\[(?:\g<main>\g<query>\g<main>)*\])/x', 'callbackReplaceNewArray', $res);
    //return $match['sign'].$res;

    if(!$match[1])
        return $match[0];

    $res = substr(substr($match[2], 1), 0, -1);
    $res = 'array('. $res . ')';
    $res = preg_replace_callback('/([\s|\(|,|=|>]*)(\[(?>[^\[\]]|(?R))*])/x', 'callbackReplaceNewArray', $res);
    return $match[1].$res;

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

function callbackReplaceArrayFunction($match)
{
    $current = 0;
    $type = substr(trim($match[0]), 0, 3);
    
    if ($type!='get' && $type!='set')
    {
        $type = $current==0? 'get' : 'set'; 
    }
    ++$current;

    return "    '$type' => ";

}

function callbackReplaceAccessors($match)
{

    $match2 = preg_replace_callback('/(?:[g|s]et\s*)?.*[^⁼>*]=>/x','callbackReplaceArrayFunction', $match[3]);
    $match2 = preg_replace('/(return)[\s]*([^\(]*)/x', "return array", $match2);
    $match2 = preg_replace('/,([\s]*\))/x', '$1', $match2);
    return 'public function '. $match[2] . 'Attribute($value, $attributes) {' . $match2 . "}\n";
}

function callbackReplaceModels($match)
{
    global $_model_list;
    if (!isset($_model_list)) $_model_list = array();

    if (in_array($match[1], $_model_list) && $match[2]!='class' && !method_exists($match[1], $match[2]))
        return "Model::instance('$match[1]')->$match[2]";

    return $match[0];
}

function callbackReplacePropertyPromotions($match)
{
    if (!$match[1])
        return $match[0];

    $parameters = explode(',', $match[1]);

    $res = '';
    $constructor = $match[0];
    foreach ($parameters as $param)
    {
        $arr = explode(' ', $param);
        $type = $arr[0];
        if (in_array(strtolower($type), array('private', 'public', 'protected')))
        {
            $p = array_pop(explode(' ', $param));
            $res .= "\n    $type $p;\n";
            $constructor = str_replace($param, str_replace($type.' ', '', $param), $constructor);
            $constructor .= "\n".'    $this->' . substr($p, 1) ." = $p;\n";
        }
    }
    return $res."\n    ".$constructor;
}


function getCallbackFromString($string)
{
	//dump($string);
	$string = str_replace(', ', ',', $string);
	$pos = strpos($string, '@');
	$class = substr($string, 0, $pos);
	$string = substr($string, $pos);
	$pos = strpos($string, '(');
	$method = substr($string, 1, $pos-1);
	$string = substr($string, $pos+1);
	$params = explode(',', substr($string, 0, -1));
	$result = array($class, $method, $params);

    if (!class_exists($class))
    {
        CoreLoader::loadClass(_DIR_.'/../../storage/framework/classes/'.$class.'.php', false);
    }

    return $result;
}

function callbackReplaceTraits($match)
{
    global $_class_list, $_trait_to_add, $_functions_to_add;

    //$_functions_to_add = array();

    $traits = explode(',', $match[1]);
    
    foreach ($traits as $trait)
    {
        $trait = trim($trait);
        $newclass = $_class_list[$trait];

        if (!$newclass)
            return $match[0];

        $text = file_get_contents($newclass);
        
        $text = preg_replace('/\)([\s]*)\{/x', ') {', $text);
        //echo '<pre>'. htmlentities($text).'</pre>';

        preg_match('/\bTrait|trait\b[\s]*.*[\s]*\{/x', $text, $content);
        $content = substr($text, strpos($text, $content[0])+strlen($content[0]));
        $_trait_to_add = rtrim(trim($content), '}');

        # Check Traits functions
        preg_match_all('/\b(public|private|protected)\b[\s]*function.*({(?:[^{}]*|(?2))*})/x', $text, $matches);

        foreach ($matches[0] as $m)
        {
            //echo '<pre>'. htmlentities($m).'</pre>';
            preg_match('/\b(public|private|protected)\b[\s]*function[\s]*([^\(]*)/', $m, $res);
            $_functions_to_add[trim($res[2])] = $m;
        }
    }
    
    return '';

}

function callbackReplaceHandle($match)
{
    $res = $match[0];

    $res = preg_replace('/[\s]*\$next[\s]*\((.*)\)/x', '$1', $res);

    return $res;

}

function replaceNewPHPFunctions($text, $classname=null, $dir=null)
{
    global $_arrow_functions, $_current_classname, $_trait_to_add, $_functions_to_add;
    
    if ($classname) $_current_classname[] = $classname;

    # Replace commented code
    //$text = preg_replace('/\/\*([^\/\*]*[\*]\/)/x', '', $text);
    //$text = preg_replace('/\/\/([^\n]*)/x', '', $text);
    $text = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/x', '', $text);
    while (preg_match('/\n[\s]*\n[\s]*\n/x', $text))
        $text = preg_replace('/\n[\s]*\n[\s]*\n/x', "\n\n", $text);

    /* if (preg_match('/'.$classname.'[\s]*extends[\s]*Middleware/', $text))
    {
        //$text = preg_replace('/,[\s]*\$next/x', ', $next=null', $text);
        $text = preg_replace('/[\s]*\$next[\s]*\((.*)\)/x', '$1', $text);
    } */

    
    # Find Traits inside classes
    $text = preg_replace_callback('/use\s[\s]*(\w[^;]*);/x', 'callbackReplaceTraits', $text);
    
    # Add trait inside class, removing existent functions;
    if (count($_functions_to_add)>0)
    {
        $text = rtrim(trim($text), "}");
        foreach ($_functions_to_add as $key => $val)
        {
            if (preg_match('/function[\s]*'.$key.'[\s]*\(/x', $text))
                $_trait_to_add = str_replace($val, '', $_trait_to_add);
        }
        $text .= $_trait_to_add . "\n}";
        //echo '<pre>'. htmlentities($text).'</pre>';
        $_functions_to_add = array();
        $_trait_to_add = '';
    }

    # something ?? else  -> isset(something)? something : else
    $text = preg_replace('/\s([^\s]*.?[^\b.*[^\?{2}])(\?{2})/x', " isset($1) ? $1 : ", $text);

    # Someclass::class to 'Someclass' and \Path\To\SomeClass::class to 'SomeClass"
    $text = str_replace('::class', '', preg_replace('/(?:[\\\|\w].*?[$\\\])?(\w*)(::class)/x', "'$1'", $text));


    # Convert [] to array()
    // this one doesn't work on PHP 5.1.6
    //$text = preg_replace_callback('/(?<sign>[\s|\(|,|=])(?<main>[^\[\]]*){0}(?<query>\[\g<main>\]|\[(?:\g<main>\g<query>\g<main>)*\])/x', 'callbackReplaceNewArray', $text);
    $text = preg_replace_callback('/([\s|\(|,|=|>]*)(\[(?>[^\[\]]|(?R))*])/x', 'callbackReplaceNewArray', $text);

    # Line breaks in functions (prevents missing some callback replacements)
    $text = preg_replace('/\)([\s]*)\{/x', ') {', $text);

    $text = preg_replace_callback('/(public[\s]*function[\s]*handle[\s]*\().*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceHandle', $text);

    # query() annonymous functions
    if ($classname)
    {
        // this one doesn't work on PHP 5.1.6
        //$text = preg_replace_callback('/(?<sign>[=|>|,|\(])[\s]*function[\s]*[\S](?<param>.*?)\)[\s]*(?<main>[^\{\}]*){0}(?<query>\{\g<main>\}|\{(?:\g<main>\g<query>\g<main>)*\})/x', 'callbackReplaceQueryes', $text);
        $text = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceQueryes', $text);
    }
    
    # New accessors and mutators: Arrow functions
    $text = preg_replace_callback('/(\w*)[\s]*function[\s]*(\w*)\(\)\s*:\s*Attribute[^{]*{([^}]*)}/x', 'callbackReplaceAccessors', $text);

    # Constructor property promotion
    $text = preg_replace_callback('/public.*__construct[\s]*\((.*)\)[\s]*{/x', 'callbackReplacePropertyPromotions', $text);


    # Generates new class for closures
    if (count($_arrow_functions)>0 && $classname)
    {
        $controller = "<?php\n\nclass baradurClosures_$_current_classname[0] {\n\n";
        foreach ($_arrow_functions as $closure)
        {
            $controller .= $closure."\n\n";
        }
        $controller .= "}";

        # Convert static model functions
        //$last_class = $_current_classname;
        //$controller = preg_replace_callback('/(\w*)::(\w*)/x', 'callbackReplaceModels', $controller);
        //$_current_classname = $last_class;

        Cache::store('file')
            ->plainPut($dir.'/../../storage/framework/classes/baradurClosures_'.$_current_classname[0].'.php', $controller);

        //require_once(_DIR_.'/../../storage/framework/classes/baradurClosures_'.$_current_classname[0].'.php');

        $controller = null;
        $_arrow_functions = array();
    }
    array_shift($_current_classname);

    # Convert static model functions
    $last_class = $_current_classname;
    $text = preg_replace_callback('/(\w*)::(\w*)/x', 'callbackReplaceModels', $text);
    $_current_classname = $last_class;


    return $text;

}

