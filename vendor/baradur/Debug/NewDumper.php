<?php

/**
 * Testing class
 * Not used since preg_replace consumes more resources
 * VarDumper class has better performance
 */

class NewDumper
{
    private $item_number = 0;
    private $current;
    private $first_item;

    private function callbackObject($match)
    {
        $this->item_number++;
        
        if ($this->current==0 && $this->first_item=='object') {
            $this->current++;
        } elseif ($this->current>0) {
            $this->current++;
        }

        $result = ltrim($match[3], '{ ');
        $result = rtrim($result, ' }');

        return ($this->current==1? '<div>' : '') .
            '<a onclick="toggleDisplay(\''.$this->item_number.'\');" style="cursor:pointer;">
            <span style="color:royalblue;">' . $match[1] . '</span>
            <span style="color:orange;"> {</span><span style="color:gray;font-size:.75rem;">' . $match[2]  . '</span>
            <span style="font-size:.8rem;color:gray;" class="mybtn" id="' . $this->item_number . 
            '_btn">&#9660;</span><span class="closing" style="color:orange;display:' . 
            ($this->current==1?'none':'inline') . ';" id="' . $this->item_number . '_close">}</span></a>
            <div id="' . $this->item_number . '" name="expandable" 
            style="margin-left:1rem;overflow:hidden;height:'. ($this->current==1?'auto':'0') .'">' . 
            $result . '</div><span id="' . $this->item_number . '_end" class="ending" 
            style="color:orange;display:'. ($this->current==1?'block':'none') .'">}</span></div>';
        
    }

    private function callbackArray($match)
    {
        $this->item_number++;
        
        if ($this->current==0 && $this->first_item=='array') {
            $this->current++;
        } elseif ($this->current>0) {
            $this->current++;
        }

        $result = ltrim($match[2], '{ ');
        $result = rtrim($result, ' }');

        return ($this->current==1? '<div>' : '') .
            '<a onclick="toggleDisplay(\''.$this->item_number.'\');" style="cursor:pointer;">
            <span style="color:royalblue;"> array:' . $match[1] . '</span>
            <span style="color:orange;"> [</span><span style="font-size:.8rem;color:gray;
            padding-left:2px;" class="mybtn" id="' . $this->item_number . 
            '_btn">&#9660;</span><span class="closing" style="color:orange;display:' . 
            ($this->current==1?'none':'inline') .';" id="' . $this->item_number . '_close">]</span></a>
            <div class="arr" id="' . $this->item_number . '" name="expandable" 
            style="margin-left:1rem;overflow:hidden;height:' . ($this->current==1?'auto':'0') . '">' . 
            $result . '</div><span id="' . $this->item_number . '_end" class="ending" 
            style="color:orange;display:'. ($this->current==1?'block':'none') .'">]</span></div>';
        
    }

    private function callbackPrivateKey($match)
    {
        $key = $match[1];
        $hidden = $key[0]=='_' ? ' hidden' : '';

        return '<div class="private' . $hidden . '"><span class="key" style="color:orange;">-</span>' . 
            $key . '<span style="color:orange;">: </span>';
    }

    private function callbackProtectedKey($match)
    {
        $key = $match[1];
        $hidden = $key[0]=='_' ? ' hidden' : '';

        return '<div class="protected' . $hidden . '"><span class="key" style="color:orange;">#</span>' . 
            $key . '<span style="color:orange;">: </span>';
    }

    private function callbackPublicKey($match)
    {
        $key = $match[1];
        $hidden = $key[0]=='_' ? ' hidden' : '';

        return '<div class="public' . $hidden . '"><span class="key" style="color:orange;">+</span>' . 
            $key . '<span style="color:orange;">: </span>';
    }

    private function callbackArrayKey($match)
    {
        $key = $match[1];

        return '<div class="array"><span class="key" style="color:royalblue;margin-left:0rem;">' .
            $key . '</span><span style="color:orange;"> => </span>';
    }


    public function process($data)
    {
        ob_start();
		var_dump($data);
		$str = ob_get_contents();
		ob_end_clean();

        $this->current = 0;

        // Find the first item (object or array)
        $obj = strpos($str, 'object');
        $arr = strpos($str, 'array');

        $this->first_item = $obj < $arr ? 'object' : 'array';

        // Empty arrays
        $str = preg_replace('/array\(0\)[\s]*\{[\s]*\}/x', '<span style="color:orange;"> []</span></div>', $str);

        // COLLAPSABLE ARRAYS
        while (strpos($str, 'array(')!==false) {
            $str = preg_replace_callback('/array\((\d*)\)[\s]*({(?:[^{}]*|(?2))*})/x', 
                array($this, 'callbackArray'), $str);
        }

        // COLLAPSABLE OBJECTS
        while (strpos($str, 'object(')!==false) {
            $str = preg_replace_callback('/object\((\w*)\)(.*?)[\s]*\([^\)]*\)[\s]*({(?:[^{}]*|(?3))*})/x', 
                array($this, 'callbackObject'), $str);
        }

        // KEYS
        $str = preg_replace_callback('/\[(\d*)\]=>/x', array($this, 'callbackArrayKey'), $str);

        $str = preg_replace_callback('/\["(\w*)":protected\]=>/x', array($this, 'callbackProtectedKey'), $str);

        $str = preg_replace_callback('/\["(\w*)":"\w*":private\]=>/x', array($this, 'callbackPrivateKey'), $str);

        $str = preg_replace_callback('/\["(\w*)"\]=>/x', array($this, 'callbackPublicKey'), $str);


        
        // VALUES
        $str = str_replace(' NULL', 
            '<span class="value" style="color:gray;font-weight:600;">null</span></div>', $str);

        $str = preg_replace('/ bool\((\w*)\)/x',
            '<span class="value" style="color:orange;font-weight:600;">$1</span></div>', $str);

        $str = preg_replace('/ int\((\w*)\)/x',
            '<span class="value" style="color:royalblue;font-weight:600;">$1</span></div>', $str);

        $str = preg_replace('/ string\(\d*\)[\s]"([^"]*)"/x', 
            '<span class="value" style="color:orange;">"</span><span style="color:#22c55e;font-weight:600;">$1
            </span><span style="color:orange;">"</span></div>', $str);


        
        return $str;
    }

}