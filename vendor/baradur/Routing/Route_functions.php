<?php

$_closures = array();
$_currentClosureFile = null;
$_arrow_functions = array();
$_macro_functions = array();
$_current_classname = array();
$_functions_to_add = array();
$_trait_to_add = '';
$_for_macro = false;

function callbackReplaceClosures($match)
{
    //dump($match);

    global $_arrow_functions, $_current_classname, $_macro_functions, $_for_macro;

    if (count($_current_classname)==0)
        return $match[0];

    $res = $match[2]; //$match['query'];
    //dump($res);

    //$res = preg_replace_callback('/(?<sign>[=|>|,|\(])[\s]*function[\s]*[\S](?<param>.*?)\)[\s]*(?<main>[^\{\}]*){0}(?<query>\{\g<main>\}|\{(?:\g<main>\g<query>\g<main>)*\})/x', 'callbackReplaceClosures', $res);
    $res = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceClosures', $res);

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
        {
            $params[] = trim($m);
        }
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
    
    $return = '"' . ($_for_macro? 'baradurMacros' : 'baradurClosures') . '_' . $_current_classname[0] .
        '@closure_' . count($_arrow_functions) ."(" . implode(', ', array_merge($defparms, $params)) . ')"';


    preg_match_all('/(\$\w*)/x', $match[2], $body_attrs);

    if (isset($body_attrs[0]) && in_array('$this', $body_attrs[0]))
    {
        $params[] = '$_self';
        $res = str_replace('$this', '$_self', $res);
    }

    
    $res = ltrim(trim($res), '{');
    $res = rtrim(trim($res), '}');

    $method  = 'public static function closure_'.count($_arrow_functions). "(".($default/* !=''?'$'.$default:'' */).(count($params)>0? ($default!=''?', ':'').implode(', ',$params):'').") {\n";
    $method .= /* $match[2]." = Model::instance('DB');\n". */$res."\n}\n";

    if ($_for_macro)
        $_macro_functions[] = $method;
    else
        $_arrow_functions[] = $method;

    $temp = $match[1];
    $temp = str_replace('function', '', $temp);

	return $temp.' '.$return; // $match['sign'].' '.$return;

}

function callbackReplaceMacros($match)
{
    global $_macro_functions, $_for_macro;
    $_for_macro = true;
    $res = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceClosures', $match[0]);
    $_for_macro = false;
    return $res;

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

function getBuilderMethods()
{
    global $_builder_methods;
    $methods = get_class_methods('Builder');
    $_builder_methods = array_diff($methods, array('__construct', '__call'));

    //var_dump($_builder_methods);
}

function callbackReplaceStatics($match)
{
    global $_model_list, $_resource_list;

    if (!isset($_model_list)) $_model_list = array();
    if (!isset($_resource_list)) $_resource_list = array();

    if (in_array($match[1], $_model_list) && $match[2]!='class' && !method_exists($match[1], $match[2]))
        return "Model::instance('$match[1]')->$match[2]";
        
    if (in_array($match[1], $_resource_list) && in_array($match[2], array('collection')))
        return "JsonResource::instance('$match[1]')->$match[2]";

    /* if (count($_builder_methods)==0)
        getBuilderMethods();

    foreach (Builder::getMacros() as $key => $val)
        $_builder_methods[] = $key;

    if (in_array($match[1], $_model_list) 
        && $match[2]!='class' 
        && in_array($match[2], $_builder_methods)
    ) {
        return "Model::instance('$match[1]')->$match[2]";
    } */


    /* $methods = array(
        'shouldBeStrict', 
        'preventLazyLoading', 
        'preventSilentlyDiscardingAttributes', 
        'preventAccessingMissingAttributes'
    );

    if (in_array($match[1], $_model_list) && $match[2]!='class' && !in_array($match[2], $methods))// !method_exists($match[1], $match[2]))
    {
        //$reflection = new ReflectionClass($match[1]);
        //dump( $reflection->getMethods(ReflectionMethod::IS_STATIC) );
        return "Model::instance('$match[1]')->$match[2]";
    } */

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
            $p = explode(' ', $param);
            $p = array_pop($p);
            $res .= "\n    $type $p;\n";
            $constructor = str_replace($param, str_replace($type.' ', '', $param), $constructor);
            $constructor .= "\n".'    $this->' . substr($p, 1) ." = $p;\n";
        }
    }
    return $res."\n    ".$constructor;
}


function getCallbackFromString($string)
{
	$string = str_replace(', ', ',', $string);
	$pos = strpos($string, '@');
	$class = substr($string, 0, $pos);

    $method = str_replace($class.'@', '', $string);
    $params = null;

    if (strpos($method, '(')!==false)
    {
        $pos = strpos($method, '(');
        $method = substr($method, 0, $pos);
        
        $params = str_replace($class.'@'.$method.'(', '', $string);
        $params = explode(',', substr($string, 0, -1));

    }
	$result = array($class, $method, $params);

    if (!class_exists($class))
    {
        CoreLoader::loadClass(_DIR_.'storage/framework/classes/'.$class.'.php', false);
    }

    return $result;
}

function executeCallback($class, $method, $parameters, $parent=null)
{
    $reflectionMethod = new ReflectionMethod($class, $method);
    $paramNames = $reflectionMethod->getParameters();

    if (is_string($class))
        $class = new $class;
    
    foreach ($paramNames as $parameter)
    {
        if ($parameter->getName()=='_self')
        {
            $parameters[] = $parent;
        }
    }
    
    return $reflectionMethod->invokeArgs($class, $parameters);
}


function callbackReplaceTraits($match)
{
    //print_r($match);
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
        $_trait_to_add .= rtrim(trim($content), '}');

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

    $res = preg_replace('/[\s]*\$next[\s]*\((.*)\)/x', ' $1', $res);

    return $res;

}

function callbackReplaceEnumCalls($match)
{
    global $_enum_list;
    
    if (in_array($match[1], $_enum_list))
    {
        return 'EnumHelper::instance("'.$match[1].'")->'.$match[2].($match[3]? $match[3] : '->value');
    }

    return $match[0];
}

function callbackReplaceEnums($match)
{
    global $_enum_list;

    $name = explode(':', $match[1]);
    $name = trim($name[0]);
    
    $newclass = str_replace('enum ', 'class ', $match[0]);
    $newclass = str_replace($match[1], $name.' extends EnumHelper', $newclass);
    $newclass = preg_replace('/case[\s]*(\w*)[\s]*;/x', 'case $1 = "$1";', $newclass);
    $newclass = str_replace('case ', 'protected $', $newclass);

    $_enum_list[] = $name;

    return $newclass;
}

$_extra_param_af = null;
function callbackReplaceArrowFunctions($match)
{
    $text = trim($match[2]);

    $opened = 0;
    $end = 0;

    foreach (str_split($text) as $char)
    {
        if ($char=='(' || $char=='[')
            $opened++;

        if ($char==']' || $char==')')
        {
            if ($opened>0)
                $opened--;
            else
                break;
        }

        if ($char==',' || $char==';')
        {
            if ($opened==0)
                break;
        }

        $end++;
    }
    $final = substr($text, 0, $end);
    $ending = substr($text, $end);

    preg_match_all('/(\$\w*)/x', $match[1], $default);
    preg_match_all('/(\$\w*)/x', $text, $attributes);

    $use = array();
    foreach ($attributes[0] as $attr)
    {
        if ($attr=='$this') $attr=='$_self';

        if (!in_array($attr, $default[0]))
            $use[] = $attr;
    }

    $result = str_replace($match[2], $final . '; }', $match[0]);

    $result = str_replace('fn', 'function', $result) . $ending;

    if (count($use) == 0)
        $result = preg_replace('/=>/x', "{\n\treturn ", $result, 1);
    else
        $result = preg_replace('/=>/x', ' use(' . implode(', ', $use) . ") { ", $result, 1);

    //$result = str_replace(';;', ';', $result) . $end;
    
    //dump($result);

    return $result;
}


function replaceNewPHPFunctions($text, $classname=null, $dir=null)
{
    global $_arrow_functions, $_current_classname, $_trait_to_add, 
        $_functions_to_add, $_macro_functions, $_model_list;
    
    if ($classname) $_current_classname[] = $classname;

    # Replace commented code
    $text = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/x', '', $text);
    while (preg_match('/\n[\s]*\n[\s]*\n/x', $text))
        $text = preg_replace('/\n[\s]*\n[\s]*\n/x', "\n\n", $text);
    
    # Find Traits inside classes
    $text = preg_replace_callback('/use\s[\s]*([a-zA-Z, ]*);/x', 'callbackReplaceTraits', $text);

    # Add trait inside class, removing existent functions;
    if (count($_functions_to_add)>0)
    {
        $text = rtrim(trim($text), "}");
        foreach ($_functions_to_add as $key => $val)
        {
            if (preg_match('/function[\s]*'.$key.'[\s]*\(/x', $text))
                $_trait_to_add = str_replace($val, '', $_trait_to_add);
        }
        //echo '<pre>'. htmlentities($text).'</pre>';
        $text .= "\n}";
        $_functions_to_add = array();
    }
    if ($_trait_to_add!='')
    {
        $text = rtrim(trim($text), "}");
        $text .= $_trait_to_add . "\n}";
    }
    $_trait_to_add = '';

    # something ?? else  -> isset(something)? something : else
    $text = preg_replace('/\s([^\s]*.?[^\b.*[^\?{2}])(\?{2})/x', " isset($1) ? $1 : ", $text);

    # const CREATED_AT and UPDATED_AT
    if (in_array($classname, $_model_list))
    {
        //$text = preg_replace('/protected[\s]*\$attributes[\s]*=/x', 'public \$attributes =', $text);
        $text = preg_replace('/const[^\s]*CREATED_AT/x', 'protected $_CREATED_AT', $text);
        $text = preg_replace('/const[^\s]*UPDATED_AT/x', 'protected $_UPDATED_AT', $text);
    }


    # Someclass::class to 'Someclass' and \Path\To\SomeClass::class to 'SomeClass"
    $text = str_replace('::class', '', preg_replace('/(?:[\\\|\w].*?[$\\\])?(\w*)(::class)/x', "'$1'", $text));

    # static:: to self::
    $text = preg_replace('/protected[\s]*static[\s]*function[\s]*booted/x', 'public function booted', $text);
    $text = str_replace('static::', '$this->', $text);

    # resources static wrap to _wrap
    $text = preg_replace('/public[\s]*static[\s]*\$wrap/x', 'protected $_wrap', $text);

    # __DIR__ to dirname(__FILE__)
    $text = str_replace('__DIR__', 'dirname(__FILE__)', $text);

    # __DIR__ to dirname(__FILE__)
    $text = str_replace('Blade::if', 'Blade::_if', $text);

    # Convert [] to array()
    // this one doesn't work on PHP 5.1.6
    //$text = preg_replace_callback('/(?<sign>[\s|\(|,|=])(?<main>[^\[\]]*){0}(?<query>\[\g<main>\]|\[(?:\g<main>\g<query>\g<main>)*\])/x', 'callbackReplaceNewArray', $text);
    $text = preg_replace_callback('/([\s|\(|,|=|>]*)(\[(?>[^\[\]]|(?R))*])/x', 'callbackReplaceNewArray', $text);


    # New accessors and mutators: Arrow functions
    $text = preg_replace_callback('/(\w*)[\s]*function[\s]*(\w*)\(\)\s*:\s*Attribute[^{]*{([^}]*)}/x', 'callbackReplaceAccessors', $text);

    # arrow function to annonymous function
    $text = preg_replace('/fn[\s]*\(/x', "\nfn (", $text); // First we need to be sure they're in separate lines
    $text = preg_replace_callback('/fn[\s]*\(([^\)]*)\)[\s]*\=\>(.*)/x', 'callbackReplaceArrowFunctions', $text);
    $text = str_replace("\nfunction", ' function', $text);


    # Line breaks in functions (prevents missing some callback replacements)
    $text = preg_replace('/\)([\s]*)\{/x', ') {', $text);

    # [val, val, val,] to [val, val, val] (remove extra comma at the end of arrays)
    $text = preg_replace('/,([\s]*)\]/x', "$1]", $text);

    # '$next($request)' to '$request' 
    $text = preg_replace_callback('/(public[\s]*function[\s]*handle[\s]*\().*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceHandle', $text);

    # macros
    $text = preg_replace_callback('/Builder\:\:macro[\s]*\((.*)function.*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceMacros', $text);

    # query() annonymous functions
    if ($classname)
    {
        // this one doesn't work on PHP 5.1.6
        //$text = preg_replace_callback('/(?<sign>[=|>|,|\(])[\s]*function[\s]*[\S](?<param>.*?)\)[\s]*(?<main>[^\{\}]*){0}(?<query>\{\g<main>\}|\{(?:\g<main>\g<query>\g<main>)*\})/x', 'callbackReplaceClosures', $text);
        $text = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', 'callbackReplaceClosures', $text);
    }

    # enums to class extending EnumHelper
    $text = preg_replace_callback('/enum[\s]*(\S*)[\s]*\{[^\}]*\}/x', 'callbackReplaceEnums', $text);
    
    # enum calls
    $text = preg_replace_callback('/(\w*)::(\w*)([->\w]*)/x', 'callbackReplaceEnumCalls', $text);
    
    # Constructor property promotion
    $text = preg_replace_callback('/public.*__construct[\s]*\((.*)\)[\s]*{/x', 'callbackReplacePropertyPromotions', $text);

    # clone() to _clone()
    $text = str_replace('->clone(', '->_clone(', $text);


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
        $last_class = $_current_classname;
        $controller = preg_replace_callback('/(\w*)::(\w*)/x', 'callbackReplaceStatics', $controller);
        $_current_classname = $last_class;

        Cache::store('file')
            ->plainPut($dir.'/storage/framework/classes/baradurClosures_'.$_current_classname[0].'.php', $controller);

        //require_once(_DIR_.'storage/framework/classes/baradurClosures_'.$_current_classname[0].'.php');

        $controller = null;
        $_arrow_functions = array();
    }

    # Generates new class for macros
    if (count($_macro_functions)>0 && $classname)
    {
        $controller = "<?php\n\nclass baradurMacros_$_current_classname[0] extends Builder {\n\n";
        foreach ($_macro_functions as $closure)
        {
            $controller .= str_replace('public static', 'public ', $closure)."\n\n";
        }
        $controller .= "}";
        
        Cache::store('file')
            ->plainPut($dir.'/storage/framework/classes/baradurMacros_'.$_current_classname[0].'.php', $controller);

        require_once(_DIR_.'storage/framework/classes/baradurMacros_'.$_current_classname[0].'.php');

        $controller = null;
        $_macro_functions = array();
    }
    array_shift($_current_classname);

    # Convert static functions in models/resources
    $last_class = $_current_classname;
    $text = preg_replace_callback('/(\w*)::(\w*)/x', 'callbackReplaceStatics', $text);
    $_current_classname = $last_class;


    return $text;

}

