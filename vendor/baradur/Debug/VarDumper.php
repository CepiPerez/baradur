<?php

Class VarDumper
{
    private static $matches = array();
    private static $current_count = 0;
    private static $object_count = 0;
    private static $show_all = false;

    private static function getKey($key)
    {
        if ($key[0] == "\0") {
            $keyParts = explode("\0", $key);
            return ($keyParts[1]=='*' ? '#' : '-') . $keyParts[2];
        } else {
            return '+' . $key;
        }
    }

    private static function getVirtuals($virtual, $object)
    {
        $arr = array();

        foreach ($virtual as $key => $val) {
            if (is_closure($val)) {
                list($class, $method) = getCallbackFromString($val);
                $arr['+'.$key] = executeCallback($class, $method, array($object));
            } else {
                $arr['+'.$key] = $val;
            }
        }

        return $arr;
    }

    private static function filterOnlyObject($attributes, $filters)
    {
        if (count($filters)==0) {
            return $attributes;
        }

        $filtered = array();

        foreach ($attributes as $attr) {
            $val = substr($attr, 1);

            if (in_array($val, $filters)) {
                $filtered[] = $attr;
            }
        }

        return $filtered;

    }

    private static function reorderObjectAttributes($attributes, $rules)
    {
        if (count($rules)==0) {
            return $attributes;
        }

        $reordered = array();

        foreach ($rules as $rule) {

            foreach ($attributes as $attr) {

                if (strpos($rule, '*')===0) {
                    $temp = str_replace('*', '', $rule);

                    if (strpos($attr, $temp)>0) {
                        $reordered[] = $attr;
                    }
                }

                if (strpos($rule, '*')>0) {
                    $temp = str_replace('*', '', $rule);

                    if (strpos($attr, $temp)===0) {
                        $reordered[] = $attr;
                    }
                }

                if (strpos($rule, '*')===false) {
                    if ($attr===$rule) {
                        $reordered[] = $attr;
                    }
                }
            }
        }

        foreach($attributes as $attr) {
            if (!in_array($attr, $reordered)) {
                $reordered[] = $attr;
            }
        }
        
        return $reordered;
    }

    private static function getObjectKeys($object)
    {
        $keys = array();

        foreach ($object as $key => $val)
        {
            $keys[] = self::getKey($key);
        }
        

        //var_dump($attributes);

        return $keys;
    }

    private static function getObjectCaster($object)
    {
        if (isset(CustomCaster::$defaultCasters[get_class($object)])) {
            return CustomCaster::$defaultCasters[get_class($object)];
        } elseif ($object instanceof Collection) {
            return CustomCaster::$defaultCasters['Collection'];
        } elseif ($object instanceof Model) {
            return CustomCaster::$defaultCasters['Model'];
        } elseif ($object instanceof Builder) {
            return CustomCaster::$defaultCasters['Builder'];
        }
        return null;
    }

    private static function processData($subject, $depth = 1, $refChain = array())
    {
        $res = '';
        $caster = null;
        $virtual = array();
        $filter = false;

        if (is_object($subject)) 
        {
            $class_name = get_class($subject);

            $attributes = /* $subject instanceof Model
                ? self::getAttributeKeys($subject->getAttributes())
                : */ self::getObjectKeys((array)$subject);

            $caster = self::getObjectCaster($subject);
 
            if ($caster) {

                if (array_key_exists('filter', $caster->operations)) {
                    $filter = true;
                }

                if (array_key_exists('only', $caster->operations)) {
                    $attributes = self::filterOnlyObject($attributes, $caster->operations['only']);
                }

                if (array_key_exists('virtual', $caster->operations)) {
                    $virtual = self::getVirtuals($caster->operations['virtual'], $subject);

                    foreach (array_keys($virtual) as $key) {
                        $val = substr($key, 1);
                        if (in_array('#'.$val, $attributes)) {
                            $k = array_search('-'.$val, $attributes);
                            $attributes[$k] = $key;
                        } elseif (in_array('-'.$val, $attributes)) {
                            $k = array_search('-'.$val, $attributes);
                            $attributes[$k] = $key;
                        } else {
                            $attributes[] = $key;
                        }
                    }
                }

                if (array_key_exists('reorder', $caster->operations)) {
                    $reorder = $caster->operations['reorder'];
                    $attributes = self::reorderObjectAttributes($attributes, $reorder);

                }
            }

            //var_dump($attributes); echo "<br>";
            //var_dump($virtual); echo "<br>";

            /* if ($subject instanceof Collection) {
                $only = array('items', 'pagination');
            } elseif ($subject instanceof Model) {
                $only = array('attributes', 'relations');
            } elseif ($subject instanceof Carbon) {
                $only = array('date');
            } */

            foreach ($refChain as $refVal)
            {
                if ($refVal === $subject) {
                    $res .= "*RECURSION*<br>";
                    return;
                }
            }

            array_push($refChain, $subject);

            $id = 'Item'.self::$current_count;
            self::$current_count++;

            $sid = empty(self::$matches)? null : self::$matches[self::$object_count];
            self::$object_count++;

            $res .= '<a onclick="toggleDisplay(\''.$id.'\');" style="cursor:pointer;">
                <span style="color:#1299da;">'.$class_name.'</span>
                <span style="color:#ff8400;margin:0;padding:0"> {</span>'.($sid?'<span style="color:gray;
                ">'.$sid.' </span>':'').'<span style="padding:0;margin:0 1px 0 2px;
                color:gray;" class="mybtn" id="'.$id.'_btn">'.($depth==1?'&#9660;':'&#9654;').
                '</span><span class="closing" style="color:#ff8400;display:'.($depth==1?'none':'default').
                ';padding:0;margin:0;" id="'.$id.'_close">}</span></a>
                <div id="'.$id.'" name="expandable" style="height:'.($depth==1?'auto':'0').';overflow:hidden;">';

            $final_subject = array();

            foreach ((array)$subject as $key => $val) {
                $final_subject[self::getKey($key)] = $val;
            }

            $reflectionClass = new ReflectionClass($subject);
            $default_props = array_keys($reflectionClass->getDefaultProperties());
            //print_r($reflectionClass->getDefaultProperties());
            $dynamic_props = array_keys(get_object_vars($subject));
            $dynamic = array_diff($dynamic_props, $default_props);

            foreach ($attributes as $attr)
            {
                $type = substr($attr, 0, 1);
                $name = substr($attr, 1);

                if (($filter && (filled($final_subject[$attr]) || isset($virtual[$attr]))) || !$filter) {

                    //$ref = self::getKey($attr);
    
                    //if ( ((self::$show_all || empty($only)) && $ref['name'][0]!='_')
                    //    || (in_array($ref['name'], $only))
                    //) {
                        $res .= '<span style="margin-left:'.($depth * 1.25).'rem;">';
                        $res .= '<span style="color:#ff8400;">'.$type.(in_array($name, $dynamic)?'"':'').'</span>'.
                            (isset($virtual[$attr]) ? '<span style="color:#B729D9;">'.$name.'</span>' : $name);
                        $res .= '</span><span style="color:#ff8400;">'.(in_array($name, $dynamic)?'"':'').': </span>';
                        $res .= isset($virtual[$attr])
                            ? self::processData($virtual[$attr], $depth +1)
                            : self::processData($final_subject[$attr], $depth + 1, $refChain);
                    //}
                }
            }

            /* foreach ($virtual as $key => $val)
            {
                $res .= '<span style="margin-left:'.($depth * 1.25).'rem;">';
                $res .= '<span style="color:#ff8400;">+<span style="color:#c026d3;">'.$key;
                $res .= '</span>: </span>';
                $res .= self::processData($val, $depth + 1, $refChain);
            } */

            $res .= '<span style="color:#ff8400;margin-left:'.(($depth-1)*1.25).'rem;">}</span></div>';
            array_pop($refChain);
        } 
        elseif (is_array($subject)) 
        {
            $id = 'Item'.self::$current_count;
            self::$current_count++;

            if (count($subject) > 0) {
                $res .= '<a onclick="toggleDisplay(\''.$id.'\');" style="cursor:pointer;">
                    <span style="color:#1299da;"> array:' . count($subject) . '</span>
                    <span style="color:#ff8400;margin:0;padding:0"> [</span><span style="padding:0;
                    margin:0 1px 0 2px;color:gray;" class="mybtn" id="'.$id.
                    '_btn">'.($depth==1?'&#9660;':'&#9654;').'</span><span class="closing" 
                    style="color:#ff8400;display:'.($depth==1?'none':'default').
                    ';padding:0;margin:0;" id="'.$id.'_close">]</span></a>
                    <div id="'.$id.'" name="expandable" style="height:'.($depth==1?'auto':'0').';overflow:hidden;">';                
            } else {
                $res .= '
                    </span><span style="color:#ff8400;"> []</span>
                    <div id="'.$id.'" name="expandable" style="height:'.($depth==1?'auto':'0').';overflow:hidden;">';
            }
            
            foreach ($subject as $key => $val)
            {
                $res .= self::printKey($key, $depth, is_assoc($subject));
                $res .=	'<span style="color:#ff8400;"> => </span></span>';
                $res .= self::processData($val, $depth + 1, $refChain);
            }

            if (count($subject) > 0) {
                $res .= '<span style="color:#ff8400;margin-left:'.(($depth-1)*1.25).'rem;">]</span></div>';
            } else {
                $res .= '</div>';
            }

        } 
        else/* if (in_array($subject, $only) || self::$show_all || empty($only)) */
        {
            $res .= self::printValue($subject, $depth) . "<br>";
        }

        return $res;
    }

    private static function printValue($value, $depth)
    {
        if (is_string($value)) {
            if (strpos($value, "\n")===false) {
                return '<span style="color:#ff8400;">"' .
                    '<span style="color:#56DB3A;font-weight:550;">' .$value . '</span>' .
                    '"</span>';
            } else {
                $lines = array();
                foreach (explode("\n", $value) as $line) {
                    $lines[] = $line . '<br>';
                }
                return '<span style="color:#ff8400;">"""</span>' .
                    '<div style="color:#56DB3A;font-weight:550;margin-left:'.(($depth)*1.25).'rem;">' .
                    implode('<br>', explode("\n", $value)) .'</div>' .
                    '<span style="color:#ff8400;margin-left:'.(($depth)*1.25).'rem;">"""</span>';
            }
        } elseif ($value===null) {
            return '<span style="color:gray;font-weight:550;">null</span>';
        }  elseif (is_bool($value)) {
            return '<span style="color:#ff8400;font-weight:550;">' . ($value? 'true' : 'false') . '</span>';
        } else {
            return '<span style="color:#1299da;font-weight:550;">' . $value . '</span>';
        }
    }

    private static function printKey($key, $depth, $is_assoc)
    {
        if ($is_assoc) {
            return is_string($key)
                ? '<span style="color:#ff8400;margin-left:'.($depth * 1.25).'rem;">"' .
                  '<span style="color:#56DB3A;">' . $key . '</span>' .
                  '"</span>'
                : '<span style="color:#56DB3A;margin-left:'.($depth * 1.25).'rem;">' . $key . '</span>';
        }

        return '<span style="color:#1299da;margin-left:'.($depth * 1.25).'rem;">' . $key;
    }

    public static function getDump($data, $full=false, $matches=array())
    {
        self::$show_all = $full;
        self::$matches = $matches;
        self::$object_count = 0;
        
        return self::processData($data);
    }


}