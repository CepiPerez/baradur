<?php

Class PHPConverter
{
    public $_arrow_functions = array();
    public $_builder_macros = array();
    public $_collection_macros = array();
    public $_current_classname = array();
    public $_functions_to_add = array();
    public $_trait_to_add = '';
    public $_for_macro = null;
    
    
    function callbackReplaceClosures($match)
    {
        //dump($match);
    
        if (count($this->_current_classname)==0)
            return $match[0];
    
        $res = $match[2]; //$match['query'];
        //dump($res);
    
        $res = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceClosures'), $res);
    
        preg_match('/function[\s]*[\S](.*?)\)(:?.*use[\s]*\(([^\)]*)\))?/x', $match[0], $matches);
        //dump($matches);
        $params = array();
        //$params[] = "'".$match[2]."'";
        $default = $matches[1]; //str_replace('$', '', $matches[1]);
        //dump("DEFAULT: ".$default);
    
        //$use_params = 0;
        if (count($matches)>3)
        {
            $replace = trim($matches[2]);
            $res = str_replace($replace, '', $res);
            foreach (explode(',', $matches[3]) as $m)
            {
                //$res = str_replace($m, '$_use_'.trim(ltrim($m, '$')), $res);
                //$params[] =  '$_use_'.trim(ltrim($m, '$'));
                $params[] = trim($m);
                //$use_params++;
            }
        }
        //$params[] = '$_baradur_use='.$use_params;
    
        $defparms = array();
        if ($default)
        {
            foreach (explode(',', $default) as $def)
            {
                $temp = trim($def);
                if ($temp) {
                    if (strpos($temp, ' ')!==false && strpos($temp, '=')===false) {
                        $arr = explode(' ', $temp);
                        $temp = $arr[1];
                    }
                    if (strpos($temp, '=')!==false) {
                        $arr = explode('=', $temp);
                        $temp = trim($arr[0]);
                    }
                    $defparms[] = $temp.count($this->_arrow_functions);
                }
                    
            }
        }
        //var_dump($defparms);
        //$params[] = '$_baradur_send='.(count($defparms) + count($params));
        $to_send = count($defparms) + count($params);
    
        $counter = array();
        if ($this->_for_macro == 'Builder')
            $counter = count($this->_builder_macros);
        elseif ($this->_for_macro == 'Collection')
            $counter = count($this->_collection_macros);
        else
            $counter = count($this->_arrow_functions);
        

        // working
        /* $return = '"' . ($this->_for_macro? 'baradur'. $this->_for_macro .'Macros' : 'baradurClosures') .
            '_' . $this->_current_classname[0] . '|closure_' . $counter . '|' . 
            $to_send . '|' . implode(', ', array_merge($defparms, $params)) . '"'; */

        // last
        $return = 'array("' . ($this->_for_macro? 'baradur'. $this->_for_macro .'Macros' : 'baradurClosures') .
            '_' . $this->_current_classname[0] . '|closure_' . $counter . '|' . 
            $to_send . '"' . (count(array_merge($defparms, $params))>0 ? ', ' : '') . 
            implode(', ', array_merge($defparms, $params)) . ')';
            
    
        preg_match_all('/(\$\w*)/x', $match[2], $body_attrs);
    
        $res = ltrim(trim($res), '{');
        $res = rtrim(trim($res), '}');
    
        $method  = 'public function closure_'.$counter. "(".
            ($default/* !=''?'$'.$default:'' */).
            (count($params)>0? ($default!=''?', ':'').
            implode(', ',$params):'')
            .") {\n";

        $method .= /* $match[2]." = Model::instance('DB');\n". */$res."\n}\n";
    
        if ($this->_for_macro == 'Builder')
            $this->_builder_macros[] = $method;
        elseif ($this->_for_macro == 'Collection')
            $this->_collection_macros[] = $method;
        else
            $this->_arrow_functions[] = $method;
    
        $temp = $match[1];
        $temp = str_replace('function', '', $temp);
    
        return $temp.' '.$return; // $match['sign'].' '.$return;
    
    }
    
    function callbackReplaceBuilderMacros($match)
    {
        $this->_for_macro = 'Builder';
        $res = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceClosures'), $match[0]);
        $this->_for_macro = null;
        return $res;
    }
    
    function callbackReplaceCollectionMacros($match)
    {
        $this->_for_macro = 'Collection';
        $res = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceClosures'), $match[0]);
        $this->_for_macro = null;
        return $res;
    }
    
    function callbackReplaceNewArray($match)
    {
        if(!$match[1])
            return $match[0];
    
        $res = substr(substr($match[2], 1), 0, -1);
        $res = 'array('. $res . ')';
        $res = preg_replace_callback('/([\s|\(|,|=|>]*)(\[(?>[^\[\]]|(?R))*])/x', array($this, 'callbackReplaceNewArray'), $res);
        return $match[1].$res;
    }
    
    function callbackReplaceNewArraySet($match)
    {
        if(!$match[1])
            return $match[0];
    
        return "\nlist(" . str_replace('[', '', str_replace(']', '', $match[1])) . ') =';
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
        $match2 = preg_replace_callback('/(?:[g|s]et\s*)?.*[^⁼>*]=>/x', array($this, 'callbackReplaceArrayFunction'), $match[3]);
        $match2 = preg_replace('/(return)[\s]*([^\(]*)/x', "return array", $match2);
        $match2 = preg_replace('/,([\s]*\))/x', '$1', $match2);
        return 'public function '. $match[2] . 'Attribute($value, $attributes) {' . $match2 . "}\n";
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
    
    function callbackReplaceTraits($match)
    {
        //print_r($match);
        global $_class_list;
    
        //$this->_functions_to_add = array();
    
        $traits = explode(',', $match[1]);
        
        foreach ($traits as $trait)
        {
            $trait = trim($trait);
            $newclass = _DIR_ . $_class_list[$trait];
    
            if ($newclass)
            {
                $text = file_get_contents($newclass);
        
                $text = preg_replace('/\)([\s]*)\{/x', ') {', $text);
                //echo '<pre>'. htmlentities($text).'</pre>';
        
                preg_match('/\bTrait|trait\b[\s]*.*[\s]*\{/x', $text, $content);
                $content = substr($text, strpos($text, $content[0])+strlen($content[0]));
                $this->_trait_to_add .= rtrim(trim($content), '}');
        
                # Check Traits functions
                preg_match_all('/\b(public|private|protected)\b[\s]*function.*({(?:[^{}]*|(?2))*})/x', $text, $matches);
        
                foreach ($matches[0] as $m)
                {
                    //echo '<pre>'. htmlentities($m).'</pre>';
                    preg_match('/\b(public|private|protected)\b[\s]*function[\s]*([^\(]*)/', $m, $res);
                    $this->_functions_to_add[trim($res[2])] = $m;
                }
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

        if ($match[3] && $match[3]=='(') {
            return $match[0];
        }

        if (array_key_exists($match[1], $_enum_list))
        {
            if (!in_array($match[2], array('cases', 'from', 'tryFrom'))) {
                return 'EnumHelper::instance("'.$match[1].'")->set("'.$match[2].'")'.($match[3]? $match[3] : '->value');
            }
        }
    
        return $match[0];
    }

    function callbackReplaceEnumsStatics($match)
    {
        return 'EnumHelper::instance(\'!ENUM!\')->'.$match[1];
    }
    
    function callbackReplaceEnums($match)
    {
        global $_enum_list;
    
        $name = explode(':', $match[1]);
        $name = trim($name[0]);

        /* $cases = '   protected static $__cases = array(';
        preg_match_all('/case[\s]*(\w*)[\s]=/x', $match[2], $matches);
        foreach ($matches[1] as $cm) {
            $cases .= '"' . str_replace('case ', '', str_replace('=', '', $cm)) . '",';
        }
        $cases = rtrim($cases, ',') . ");\n  protected static function getCases() {\n";
        $cases .= '    return self::$__cases;' . "\n  }\n}"; */

        $backed = false;
        $backed_type = null;


        if (strpos($match[1], ':')!==false) {
            $temp = $match[1];
            $temp = explode(':', $temp);
            //$backed_type = end(explode(':', $temp));
            $temp = reset($temp);
            $backed = true;
        }

        $newclass = 'class '.$name.' extends EnumHelper'."\n".$match[2];
        //$newclass = str_replace($match[1], $name.' extends EnumHelper', $newclass);
        $newclass = str_replace('static::', 'self::', $newclass);
        $newclass = preg_replace('/case[\s]*(\w*)[\s]*;/x', 'case $1 = "$1";', $newclass);
        $newclass = preg_replace('/(\$this)([^-])/x', '$1->value$2', $newclass);
        $newclass = str_replace('case ', 'public $', $newclass);

        $newclass = preg_replace_callback('/self::(\w*)/x', 
            array($this, 'callbackReplaceEnumsStatics'), $newclass);

        $newclass = str_replace('!ENUM!', $name, $newclass);

        $newclass = rtrim(trim($newclass), '}');
        $newclass .= '
    public static function cases()
    {
        $arr = array();
        $instance = new self;

        foreach ($instance as $k => $v){
            if ($k!="name" && $k!="value") {
                $item = new '.$name.';
                $item->value = $v;
                $item->name = $k;
                $arr[] = $item;
            }
        }

        return $arr;
    }';

        if ($backed) {
            $newclass .= '
    public static function from($value)
    {
        $instance = new self;

        foreach ($instance as $k => $v){
            if ($v==$value) {
                $instance->name = $k; 
                $instance->value = $v;
                return $instance;
            }
        }

        throw new ValueError("Value [$value] not found in ".get_class($this).".");
    
    } 

    public static function tryFrom($value)
    {
        $instance = new self;

        foreach ($instance as $k => $v){
            if ($v==$value) {
                $instance->name = $k; 
                $instance->value = $v;
                return $instance;
            }
        }

        return null;
    
    }
    ';
        }

        $newclass .= '' . "\n}";

        $_enum_list[$name] = $this->_current_classname[0];
        return $newclass;
    }

    function callbackReplaceLineBreakArrowFunctions($match)
    {
        return str_replace("\n", '', $match[0]);
    }
    
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
            //if ($attr=='$this') $attr=='$_self';
    
            if (!in_array($attr, $default[0]))
                $use[] = $attr;
        }
    
        $result = str_replace($match[2], $final . '; }', $match[0]);
    
        $result = str_replace('fn', 'function', $result) . $ending;
    
        if (count($use) == 0)
            $result = preg_replace('/=>/x', "{\n\treturn ", $result, 1);
        else
            $result = preg_replace('/=>/x', ' use(' . implode(', use_', $use) . ") { ", $result, 1);
    
        //$result = str_replace(';;', ';', $result) . $end;
        
        //dump($result);
    
        return $result;
    }

    function callbackReplaceArrowFunctionsNew($match)
    {
        $text = trim($match[2]);
    
        $opened = 0;
        $end = 0;
    
        foreach (str_split($text) as $char)
        {
            if ($char=='(' || $char=='[' || $char=='{')
                $opened++;
    
            if ($char==']' || $char==')' || $char=='}')
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
            //if ($attr=='$this') $attr=='$_self';
    
            if (!in_array($attr, $default[0]))
                $use[] = $attr;
        }
    
        $result = str_replace($match[2], $final . '; }', $match[0]);
    
        $result = str_replace('fn', 'function', $result) . $ending;
    
        if (count($use) == 0)
            $result = preg_replace('/=>/x', "{\n\treturn ", $result, 1);
        else
            $result = preg_replace('/=>/x', ' use(' . implode(', use_', $use) . ") { ", $result, 1);
    
        //$result = str_replace(';;', ';', $result) . $end;
        
        //dump($match);
    
        return $result;
    }
    
    function callbackReplaceInvokable($match)
    {
        return str_replace('(', '', $match[0]) . '->__invoke(';
    }
    
    function callbackReplaceClasses($match)
    {
        $array = explode('\\', $match[1]);
        return "'" . end($array) . "'";
    }
    
    function callbackReplaceStatics($match)
    {
        global $_model_list, $_resource_list;
    
        if (!isset($_model_list)) $_model_list = array();
        if (!isset($_resource_list)) $_resource_list = array();
    
        if (in_array($match[1], $_model_list) && $match[2]!='class' && !method_exists($match[1], $match[2]))
            return "Model::instance('$match[1]')->$match[2]";
            
        if (in_array($match[1], $_resource_list) && in_array($match[2], array('collection', 'make')))
            return "JsonResource::instance('$match[1]')->$match[2]";
    
        return $match[0];
    }

    function callbackReplaceMatchDefault($match)
    {
        if ($match[2]!=='true')
            return $match[0];

        $value = explode(' ', $match[1]);

        return str_replace($match[2], end($value), $match[0]);
    }

    function callbackReplaceMatchDefaultClass($match)
    {
        if ($match[2]!=='true')
            return $match[0];

        $value = explode(' ', $match[1]);

        return str_replace($match[2], end($value), $match[0]);
    }

    function callbackReplaceMatch($match)
    {
        $function = trim($match[2]);
        $function = preg_replace('/,\s*\}/x', '', $function);
        $function = ltrim($function, '{');
        $function = rtrim($function, '}');
        $function = str_replace('default', '"default"', $function);

        return '__match (' . $match[1] . ', array(' . $function . '))';
    }

    public function callbackReplaceFailMethod($match)
    {
        $res = $match[0];
        $res = str_replace($match[1], 'return(', $res);
        return $res;
    }

    public function callbackDefineHome($match)
    {
        return 'const HOME = "'.$match[1].'";';
    }

    public function callbackReplaceDeclarations($match)
    {
        $text = $match[0];

        # Remove namespaces
        $text = preg_replace('/namespace[\s]+.+?;\n/x', '', $text);

        # Remove use
        $text = preg_replace('/use[\s]+.*;\n/x', '', $text);
        
        return $text;
    }

    public function callbackReplaceRequired($match)
    {
        $file = $match[2];

        $file = str_replace('(', '', str_replace(')', '', $match[2]));

        $file = str_replace('__DIR__', '', $file);
        $file = ltrim($file, '.');

        return 'CoreLoader::loadClass(!DIR!' . $file . ', false);';
    }
    

    public function replaceTernaries($text)
    {
        # short ternary / null coalescing operators
        //$text = preg_replace('/\s([^\s]*.?[^\b.*[^\?{2}])(\?{2})/x', " $1!==null ? $1 : ", $text);
        $text = preg_replace('/([a-zA-Z\$_\-]*(\[(?>[^\[\]]|(?R))*\])*(\((?>[^\(\)]|(?R))*\))*[\s]*[^\?{2}])\?\?[\s]*/x', " $1!== null ? $1 : ", $text);
        $text = preg_replace('/([a-zA-Z\$_\-]*(\[(?>[^\[\]]|(?R))*\])*(\((?>[^\(\)]|(?R))*\))*[\s]*[^\?{2}])\?\:[\s]*/x', " filled($1) ? $1 : ", $text);

        return $text;
    }

    public function replaceArrayVariables($text)
    {
        # Convert [] to array()
        // this one doesn't work on PHP 5.1.6
        //$text = preg_replace_callback('/(?<sign>[\s|\(|,|=])(?<main>[^\[\]]*){0}(?<query>\[\g<main>\]|\[(?:\g<main>\g<query>\g<main>)*\])/x', 'callbackReplaceNewArray', $text);
        $text = preg_replace_callback('/[^\]|\S](\[(?>[^\[\]]|(?R))*])[\s]*=/x', array($this, 'callbackReplaceNewArraySet'), $text);
        $text = preg_replace_callback('/([\s|\(|,|=|>]*)(\[(?>[^\[\]]|(?R))*])/x', array($this, 'callbackReplaceNewArray'), $text);

        # [val, val, val,] to [val, val, val] (remove extra comma at the end of arrays)
        $text = preg_replace('/,([\s]*)\]/x', "$1]", $text);
                
        return $text;
    }
    
    public function replaceNewPHPFunctions($text, $classname=null, $dir=null, $is_migration=false)
    {
        global $_model_list, $_class_list, $_enum_list;
        
        if ($classname) $this->_current_classname[] = $classname;
    
        # Remove declarations (namespace, use)
        //$text = preg_replace_callback('/\<[\s]*\?[\s]*php[\s]*.*[^\{]*/mx', array($this, 'callbackReplaceDeclarations'), $text);

        # HOME Constant
        $text = preg_replace_callback('/public[\s]*const[\s]*HOME[\s]=[\s]*[\'|\"](.*)[\'|\"];/x', array($this, 'callbackDefineHome'), $text);

        # Remove public|protected|private from Constants
        $text = preg_replace('/(public|protected|private)[\s]*(const[\s]*.*;)/x', "$2", $text);

        # New accessors and mutators: Arrow functions
        $text = preg_replace_callback('/(\w*)[\s]*function[\s]*(\w*)\(\)\s*:\s*Attribute[^{]*{([^}]*)}/x', array($this, 'callbackReplaceAccessors'), $text);

        # Replace data types in functions
        $text = preg_replace('/([\(|,][\s]*)(int|float|string|array|bool|mixed)[\s]*(\$)/x', '$1$3', $text);
        
        # Replace data types in functions return
        $text = preg_replace('/(function.*\)[\s]*)(:[\s]*[\w]*)/x', '$1', $text);

        # Replace commented code
        $text = preg_replace('/(?:(?:\B\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/x', '', $text);
        while (preg_match('/\n[\s]*\n[\s]*\n/x', $text)) {
            $text = preg_replace('/\n[\s]*\n[\s]*\n/x', "\n\n", $text);
        }
        
        # const CREATED_AT - UPDATED_AT - DELETED_AT
        if (in_array($classname, $_model_list)) {
            //$text = preg_replace('/protected[\s]*\$attributes[\s]*=/x', 'public \$attributes =', $text);
            $text = preg_replace('/const[\s]*CREATED_AT/x', 'protected $_CREATED_AT', $text);
            $text = preg_replace('/const[\s]*UPDATED_AT/x', 'protected $_UPDATED_AT', $text);
            $text = preg_replace('/const[\s]*DELETED_AT/x', 'protected $_DELETED_AT', $text);
        }
        
        # Find Traits inside classes
        $text = preg_replace_callback('/use\s[\s]*([a-zA-Z, ]*);/x', array($this, 'callbackReplaceTraits'), $text);
    
        # Remove DELETED_AT in softDelete if setted in model
        if (in_array($classname, $_model_list)) {
            preg_match_all('/protected[\s]*\$_DELETED_AT.*/x', $text, $cant);
            if (count($cant[0]) > 0) {
                $this->_trait_to_add = str_replace("protected ".'$_DELETED_AT'." = 'deleted_at';", '', $this->_trait_to_add);
            }
        }
    
        # Add trait inside class, removing existent functions;
        if (count($this->_functions_to_add)>0) {
            $text = rtrim(trim($text), "}");
            foreach ($this->_functions_to_add as $key => $val)
            {
                if (preg_match('/function[\s]*'.$key.'[\s]*\(/x', $text))
                    $this->_trait_to_add = str_replace($val, '', $this->_trait_to_add);
            }
            $text .= "\n}";
            $this->_functions_to_add = array();
        }
        if ($this->_trait_to_add!='') {
            $text = rtrim(trim($text), "}");
            $text .= $this->_trait_to_add . "\n}";
        }
        $this->_trait_to_add = '';

        # exit() and die() --> __exit() 
        $text = preg_replace('/\bexit\(/x', '__exit(', $text);
        $text = preg_replace('/\bexit;/x', '__exit();', $text);
        $text = preg_replace('/\bdie\(/x', '__exit(', $text);
        $text = preg_replace('/\bdie;/x', '__exit();', $text);

        # short ternary / null coalescing operators
        $text = $this->replaceTernaries($text);

        # Someclass::class to 'Someclass' and \Path\To\SomeClass::class to 'SomeClass"
        $text = preg_replace_callback('/([\w|\\\]*)(::class)/x', array($this, 'callbackReplaceClasses'), $text);
        
        # Resources static wrap to _wrap
        //$text = preg_replace('/public[\s]*static[\s]*\$wrap/x', 'protected $_wrap', $text);

        # Sleep for / and
        $text = preg_replace('/(Sleep::for\(.*[^and\(]*)(and)\(/x', '$1andFor(' , $text);
        $text = str_replace('Sleep::for', 'Sleep::instanceFor', $text);

        # CustomCaster for
        $text = str_replace('CustomCaster::for', 'CustomCaster::instanceFor', $text);

        # RateLimiter functions
        $text = str_replace('RateLimiter::for', 'RateLimiter::instanceFor', $text);

        # Feature functions
        $text = str_replace('Feature::for(', 'Feature::instanceFor(', $text);

        # __DIR__ to dirname(__FILE__)
        $text = str_replace('__DIR__', 'dirname(__FILE__)', $text);
    
        # Blade::if workaround
        $text = str_replace('Blade::if', 'Blade::_if', $text);

        $text = $this->replaceArrayVariables($text);
                
        # Stupid true inside Match function
        $text = preg_replace_callback('/fn[\s]*\(([^\)]*)\)[\s]*\=\>[\s]*match[\s]*\(([^\)]*)\)/x', array($this, 'callbackReplaceMatchDefault'), $text);
        $text = preg_replace_callback('/function.*\((.+)\)[\s\S]*[$match][\s]*\(([^\)]*)\)/x', array($this, 'callbackReplaceMatchDefaultClass'), $text);
        
        # arrow function to annonymous function
        // First we need to be sure they're in separate lines
        $text = preg_replace('/fn[\s]*\(/x', "\nfn (", $text);
        // Support multi-line arrow functions {}
        //$text = preg_replace_callback('/fn[\s].*\s{/x', array($this, 'callbackReplaceLineBreakArrowFunctions'), $text);
        // Now we convert it to closure
        $text_new = preg_replace_callback('/fn[\s]*\(([^\)]*)\)[\s]*\=\>(.*{(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceArrowFunctionsNew'), $text);
        if ($text_new) $text = $text_new;
        $text_new = preg_replace_callback('/fn[\s]*\(([^\)]*)\)[\s]*\=\>(.*\((?:[^\(\)]*|(?2))*\).)/x', array($this, 'callbackReplaceArrowFunctionsNew'), $text);
        if ($text_new) $text = $text_new;
        $text = preg_replace_callback('/fn[\s]*\(([^\)]*)\)[\s]*\=\>(.*)/x', array($this, 'callbackReplaceArrowFunctions'), $text);
        //$text = str_replace("\nfunction", ' function', $text);
    
        # New PHP function match()
        $text = preg_replace_callback('/match[\s]*\(([^\)]*)\)[\s]*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceMatch'), $text);

        # Line breaks in functions (prevents missing some callback replacements)
        $text = preg_replace('/\)([\s]*)\{/x', ') {', $text);
        
        # throw() to __trow() (stupid old php shit)
        $text = str_replace('->throw(', '->__throw(', $text);

        # '$next($request)' to '$request' 
        $text = preg_replace_callback('/(public[\s]*function[\s]*handle[\s]*\().*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceHandle'), $text);
    
        # Builder macros
        $text = preg_replace_callback('/Builder\:\:macro[\s]*\((.*)function.*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceBuilderMacros'), $text);
    
        # Collection macros
        $text = preg_replace_callback('/Collection\:\:macro[\s]*\((.*)function.*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceCollectionMacros'), $text);
    
        # query() annonymous functions
        if ($classname) {
            // this one doesn't work on PHP 5.1.6
            //$text = preg_replace_callback('/(?<sign>[=|>|,|\(])[\s]*function[\s]*[\S](?<param>.*?)\)[\s]*(?<main>[^\{\}]*){0}(?<query>\{\g<main>\}|\{(?:\g<main>\g<query>\g<main>)*\})/x', array($this, 'callbackReplaceClosures'), $text);
            $text = preg_replace_callback('/([=|>|,|\(][\s]*function).*({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceClosures'), $text);
        }

        # enums to class extending EnumHelper
        $text = preg_replace_callback('/enum[\s]*(\S*)[\s]*(?:[\s]*\S*[\s]*)({(?:[^{}]*|(?2))*})/x', array($this, 'callbackReplaceEnums'), $text);
        
        # enum calls
        $text = preg_replace_callback('/(\w*)::(\w*)([->\w|\(]*)/x', array($this, 'callbackReplaceEnumCalls'), $text);
        
        # static:: to self::
        $text = preg_replace('/protected[\s]*static[\s]*function[\s]*booted/x', 'public function booted', $text);
        $text = str_replace('static::', '$this->', $text);

        # Constructor property promotion
        $text = preg_replace_callback('/public.*__construct[\s]*\((.*)\)[\s]*{/x', array($this, 'callbackReplacePropertyPromotions'), $text);
    
        # clone() to _clone()
        $text = str_replace('->clone(', '->_clone(', $text);

        # Validation rule Closure $fail to function __fail()
        $text = preg_replace_callback('/public[\s]function[\s]*validate[\s\S]*\{[\s\S]*(\$fail\()/x', array($this, 'callbackReplaceFailMethod'), $text);


        # Invokable: $someClass($value) to $someClass->__invoke($value)
        $text = preg_replace_callback('/[^\w]\$[a-zA-Z]*\(/x', array($this, 'callbackReplaceInvokable'), $text);
    
        if (count($_enum_list)>0) {
            @file_put_contents(_DIR_.'storage/framework/config/enums.php', serialize($_enum_list));
        }
    
        # Generates new class for closures
        if (count($this->_arrow_functions)>0 && $classname) {

            $controller = "<?php\n\nclass baradurClosures_".$this->_current_classname[0];
            
            if (array_key_exists($this->_current_classname[0], $_class_list) && !$is_migration) {
                $controller .= " extends ".$this->_current_classname[0];
            } elseif ($this->_current_classname[0]=='console') {
                $controller .= " extends Command";
            }
            
            $controller .= " {\n\n";
                
            foreach ($this->_arrow_functions as $closure) {
                $controller .= $closure."\n\n";
            }

            $controller .= "}";
    
            # Convert static model functions
            $last_class = $this->_current_classname;
            $controller = preg_replace_callback('/(\w*)::(\w*)/x', array($this, 'callbackReplaceStatics'), $controller);
            $controller = preg_replace_callback('/(\w*)::(\w*)([->\w|\(]*)/x', array($this, 'callbackReplaceEnumCalls'), $controller);
            $this->_current_classname = $last_class;
    
            Cache::store('file')
                ->plainPut($dir.'/storage/framework/classes/baradurClosures_'.$this->_current_classname[0].'.php', $controller);
    
            //require_once(_DIR_.'storage/framework/classes/baradurClosures_'.$this->_current_classname[0].'.php');
    
            $controller = null;
            $this->_arrow_functions = array();
        }
    
        # Generates new class for Builder macros
        if (count($this->_builder_macros)>0 && $classname) {
            $controller = "<?php\n\nclass baradurBuilderMacros_".$this->_current_classname[0]." extends Builder {\n\n";

            foreach ($this->_builder_macros as $closure) {
                $controller .= $closure; //str_replace('public static', 'public ', $closure)."\n\n";
            }

            $controller .= "}";
            
            Cache::store('file')
                ->plainPut($dir.'/storage/framework/classes/baradurBuilderMacros_'.$this->_current_classname[0].'.php', $controller);
        
            $controller = null;
            $this->_builder_macros = array();
        }
    
        # Generates new class for Collection macros
        if (count($this->_collection_macros)>0 && $classname) {
            $controller = "<?php\n\nclass baradurCollectionMacros_".$this->_current_classname[0]." extends Collection {\n\n";

            foreach ($this->_collection_macros as $closure) {
                $controller .= $closure; //str_replace('public static', 'public ', $closure)."\n\n";
            }
            $controller .= "}";
            
            Cache::store('file')
                ->plainPut($dir.'/storage/framework/classes/baradurCollectionMacros_'.$this->_current_classname[0].'.php', $controller);    
    
            $controller = null;
            $this->_collection_macros = array();
        }
        
        array_shift($this->_current_classname);
    
        # Convert static functions in models/resources
        $last_class = $this->_current_classname;
        $text = preg_replace_callback('/(\w*)::(\w*)/x', array($this, 'callbackReplaceStatics'), $text);
        $this->_current_classname = $last_class;
    
        return $text;
    }

    public function replaceStatics($text)
    {
        return preg_replace_callback('/(\w*)::(\w*)/x', array($this, 'callbackReplaceStatics'), $text);
    }

    public function replaceForView($text)
    {
        $text = preg_replace_callback('/(\w*)::(\w*)/x', array($this, 'callbackReplaceStatics'), $text);
        $text = $this->replaceTernaries($text);
        $text = str_replace('__DIR__', 'dirname(__FILE__)', $text);

        $text = $this->replaceArrayVariables($text);

        return $text;
    }

    public function replaceForScriptsInView($text)
    {
        $text = preg_replace_callback('/(\w*)::(\w*)/x', array($this, 'callbackReplaceStatics'), $text);
        $text = $this->replaceTernaries($text);
        $text = str_replace('__DIR__', 'dirname(__FILE__)', $text);

        return $text;
    }

    public function replaceRequired($text, $currentFolder)
    {
        $text = preg_replace_callback('/(require|require_once|include|include_once)[\s](.*);/x', array($this, 'callbackReplaceRequired'), $text);

        return str_replace('!DIR!', "_DIR_.'/".$currentFolder."/'.", $text);

    }


}


