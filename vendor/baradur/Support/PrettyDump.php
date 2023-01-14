<?php

Class PrettyDump
{
    private static $custom_objects = array();
    private static $current_count = 0;
    private static $show_all = false;

    private static function getObjectName($value)
    {

        foreach (self::$custom_objects as $key => $val)
        {
            if (!is_array($val)) $val = array($val);

            if (in_array($value, $val))
                return $key;
        }

        return 'Object';
    }

    private static function getColor($depth)
    {
        $colors = array(1=>'blue', 2=>'green', 3=>'darkslategray', 4=>'slateblue', 5=>'gray', 
            6=>'teal', 7=>'cadetblue', 8=>'slate', 9=>'maroon', 0=>'olive');

        if ($depth>10)
        {
            $depth = (int) substr($depth, -1);
        }

        return $colors[$depth];
    }

    private static function processData($subject, $ignore = array(), $depth = 1, $refChain = array())
    {
        $res = '';

        //if ($depth > 20) return;

        if (is_object($subject)) 
        {
            foreach ($refChain as $refVal)
            {
                if ($refVal === $subject)
                {
                    $res .= "*RECURSION*<br>";
                    return;
                }
            }
            array_push($refChain, $subject);

            $id = 'Item'.self::$current_count;
            self::$current_count++;

            $res .= '<a onclick="toggleDisplay(\''.$id.'\');" style="cursor:pointer;">
                <span style="color:royalblue;">'.get_class($subject) .'</span>
                <span style="color:orange;font-size:.8rem;"> &lt;'.
                self::getObjectName(get_class($subject)) .'&gt; </span>
                </a><button onclick="toggleDisplay(\''.$id.'\');" name="'.$id.'" class="btn" style="padding:0 .2rem;margin:0.5rem 0 0rem 0;font-size:.6rem;">+</button>
                <div id="'.$id.'" name="expandable" style="height:'.($depth==1?'auto':'0').';overflow:hidden;">
                ';

            if (get_class($subject)=='Collection')
            {
                if ($subject->hasPagination())
                    $res .= "<i style='margin-left:".($depth * 2)."rem;color:gray;'>&lt;has pagination&gt;</i><br>";
            }
            
            $subject = (array) $subject;
            foreach ($subject as $key => $val)
            {
                if ($key[0] != "\0" || self::$show_all)
                {
                    if (is_array($ignore) && !in_array($key, $ignore, 1))
                    {
                        $res .= "
                        <span style='margin-left:".($depth * 2)."rem;color:";
                        $res .= self::getColor($depth) .";'> [";
                        if ($key[0] == "\0")
                        {
                            $keyParts = explode("\0", $key);
                            $res .= $keyParts[2] . '<span style="color:red;font-size:.85rem;">';
                            $res .= (($keyParts[1] == '*')  ? ':protected' : ':private') . '</span>';
                        } else {
                            $res .= $key;
                        }
                        $res .= ']</span><span style="color:gray;font-size:.85rem;"> => </span>';
                        $res .= self::processData($val, $ignore, $depth + 1, $refChain);
                    }
                }
            }

            if (substr($res, -4)=="<br>")
                $res = substr($res, 0, -4);

            $res .= "</div>";
            array_pop($refChain);
        } 
        elseif (is_array($subject)) 
        {
            $id = 'Item'.self::$current_count;
            self::$current_count++;

            //<button onclick="toggleDisplay(\''.$id.'\');" style="padding:0 .2rem;margin:0.5rem 0 0rem 0;font-size:.6rem;">+</button>

            $res .= '<a onclick="toggleDisplay(\''.$id.'\');" style="cursor:pointer;">
                </span><span style="color:coral;"> Array</span>
                </a><button onclick="toggleDisplay(\''.$id.'\');" name="'.$id.'" class="btn" style="padding:0 .2rem;margin:0.5rem 0 0rem 0;font-size:.6rem;">+</button>
                <div id="'.$id.'" name="expandable" style="height:'.($depth==1?'auto':'0').';overflow:hidden;">';
            foreach ($subject as $key => $val)
            {
                if (is_array($ignore) && !in_array($key, $ignore, 1)) {
                    $res .= "<span style='margin-left:".($depth * 2)."rem;color:";
                    $res .= self::getColor($depth) .";'> [" . $key . ']';
                    $res .=	'<span style="color:gray;font-size:.85rem;"> => </span></span>';
                    $res .= self::processData($val, $ignore, $depth + 1, $refChain);
                }
            }

            if (substr($res, -4)=="<br>")
                $res = substr($res, 0, -4);	
            
            $res .= "</div>";

        } else
        {
            $res .=  ($subject===null 
                ? '<i style="color:gray;font-size:.85rem;">(null)</i>'
                : (is_bool($subject)
                    ? '<i style="color:gray;font-size:.85rem;">(' . ($subject? 'true' : 'false') . ')</i>'
                    : $subject
                )) . "<br>";
        }

        return $res;
    }

    public static function getDump($data, $full=false, $custom=array())
    {
        self::$show_all = $full;
        self::$custom_objects = $custom;

        $result = self::processData($data);

        if(strpos($result, '<button')!==false)
        {
            $result = '<button onclick="expandAll();">Expand all</button> 
            <button onclick="collapseAll();">Collapse all</button><br><br>'.$result;
        }

        echo '<style>* {font-family:monospace;font-size:13px;margin:0;}</style>
        <div style="line-height:1.4rem;background:ghostwhite;margin:.5rem;padding:1rem;
        border:1px solid lavender;">'.$result.'</div>
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
        </script>';

        ;
    }


}