<?php

define('KEY_UP', "\e[A");
define('KEY_DOWN', "\e[B");
define('KEY_RIGHT', "\e[C");
define('KEY_LEFT', "\e[D");
define('KEY_UP_ARROW', "\eOA");
define('KEY_DOWN_ARROW', "\eOB");
define('KEY_RIGHT_ARROW', "\eOC");
define('KEY_LEFT_ARROW', "\eOD");
define('KEY_DELETE', "\e[3~");
define('KEY_BACKSPACE', "\177");
define('KEY_ENTER', "\n");
define('KEY_SPACE', ' ');
define('KEY_TAB', "\t");
define('KEY_SHIFT_TAB', "\e[Z");
define('KEY_CTRL_C', "\x03");

Class Prompts
{
    private static $inputValues = array();

    private static $started = true;

    private static $initialMode = null;

    private static $cols = 65;

    private static function checkStart()
    {
        if (self::$started) {
            self::$started = false;
            self::$initialMode = shell_exec("stty -g");
            $screen_cols = (exec('tput cols')) -2;
            self::$cols = self::$cols > $screen_cols ? $screen_cols : self::$cols;
        } else {
            self::replaceCommandOutput(null);
            echo "\r\033[K\033[1A\r\033[K";
        }
        printf("\n");

        shell_exec("stty -icanon -isig -echo");
    }

    private static function replaceCommandOutput($output)
    {
        static $oldLines = 0;

        if (!$output) {
            $oldLines = 0;
            return;
        }

        $numNewLines = count($output) - 1;

        if ($oldLines != 0) {
            $pos = $numNewLines - $oldLines;
            for($i = 0; $i < $oldLines; $i++) {
                // Return to the beginning of the line
                echo "\r";
                // Erase to the end of the line
                echo "\033[K";
                // Move cursor Up a line
                echo "\033[1A";
                // Return to the beginning of the line
                echo "\r";
                // Erase to the end of the line
                echo "\033[K";
                // Return to the beginning of the line
                //echo "\r";
                // Can be consolodated into
                // echo "\r\033[K\033[1A\r\033[K\r";
                //$pos--;
                //echo "\r\033[K\033[1A\r\033[K\r";
            }
        }
        
        $oldLines = $numNewLines;
        
        echo implode(PHP_EOL, $output);
        echo chr(27) . "[0G";
        echo chr(27) . "[" . $oldLines . "A";

        $legend = "";
        if ($pos < 0) {
            for ($i=0; $i<abs($pos); $i++) {
                echo "\r";
                echo "\033[K";
                echo "\033[1A";
                echo "\r";
                echo "\033[K\n";
                $legend .= "!";
            }
        }
        
        echo /* $legend."::".$pos. */ implode(PHP_EOL, $output);//. ":::". $oldLines.":".$numNewLines;

        //  $numNewLines = $oldLines;
    }

    private static function getScrollbar($values, $showed, $scroll, $active=true)
    {
        if (count($values)<=$scroll) {
            return null;
        }

        $index = array_search(reset($showed), $values);
        $percent = $scroll / count($values);
        $barHeight = intval($percent * $scroll);
        $index = array_search(reset($showed), $values);
        $total = count($values);
        $height = $total / $scroll;

        $percent = ($index + 1 - $height) / ($total - $height);
        $position = (int) ceil($percent * $scroll);

        $res = array();

        for ($i=0; $i<$scroll; $i++) {
            if ($i==$position) {
                for ($j=0; $j<$barHeight; $j++) {
                    $res[] = $active ? "\033[38;5;6m ┃\033[38;5;240m" : "\033[38;5;248m ┃\033[38;5;240m";
                    if ($j>1) $i++;
                }
            }
            else $res[] = " │";
        }

        return $res;
    }

    private static function hideCursor()
    {
        fprintf(STDOUT, "\033[?25l");
    }

    private static function restoreTty()
    {
        shell_exec("stty ".self::$initialMode);

        fprintf(STDOUT, "\033[?25h");
    }

    private static function waitForInput()
    {
        $input = '';

        $read = [STDIN];
        $write = null;
        $except = null;
        
        readline_callback_handler_install('', function() {});

        do {
            $input .= fgetc(STDIN);
        } while (stream_select($read, $write, $except, 0, 1));

        readline_callback_handler_remove();

        return $input;
    }

    public static function show_note($message, $type)
    {
        switch ($type) {
            case 'info':
                $color = " \033[32m"; break;
            case 'note':
                $color = " \033[39m"; break;
            case 'warning':
                $color = " \033[33m"; break;
            case 'error':
                $color = " \033[31m"; break;
            case 'alert':
                $color = " \033[41m"; $message = " ".$message." "; break;
            case 'intro':
                $color = " \033[7;49;36m"; $message = " ".$message." "; break;
            case 'outro':
                $color = " \033[7;49;36m"; $message = " ".$message." "; break;                
        }


        //$message = preg_replace('/(\[.*\])/x', "\033[1m$1\033[m", $message);

        self::checkStart();
        //printf("\n  ");
        printf(" ".$color."%s", $message."\033[m");
        printf("\n\n");
        
        self::restoreTty();
    }

    public static function show_line_message($color, $type, $message)
    {
        global $artisan;

        if ($artisan) {
            $message = preg_replace('/(\[.*\])/x', "\033[1m$1\033[m", $message);

            self::checkStart();
            //printf("\n  ");
            printf("  \033[".$color."m%s", " $type ");
            printf("\033[m %s\n\n", $message);

            self::restoreTty();
        }
    }

    public static function get_user_input($title, $placeholder = '', $default = '', $required = false, $password = false, $validate = null, $hint = '')
    {
        self::checkStart();

        self::$inputValues = array(
            'title' => $title,
            'prompt' =>  $default, 
            'placeholder' => $placeholder, 
            'password' => $password,
            'required' => $required===true ? 'Required.' : $required,
            'validate' => $validate,
            'hint' => $hint,
            'position' => 1
        );

        self::hideCursor();
        
        self::drawInput();

        $userline = $default=='' ? array() : str_split($default);
        
        while (true) {
            $input = self::waitForInput();

            switch($input){
                case KEY_UP:
                    break;
                case KEY_DOWN:
                    break;
                case KEY_ENTER:
                    if (count($userline)==0 && $required!==false) {
                        self::drawInputRequired();
                    } elseif ($validate) {
                        list($class, $method) = getCallbackFromString($validate);
                        $res = executeCallback($class, $method, array(self::$inputValues['prompt']));
                        if ($res) {
                            self::drawInputRequired($res);
                        } else {
                            self::drawInputFinished();
                            break 2;
                        }
                    } else {
                        self::drawInputFinished();
                        break 2;
                    }
                    break;
                case KEY_BACKSPACE:
                    if (count($userline)>0) {
                        $pos = self::$inputValues['position']-2;
                        $userline = array_merge(
                            array_slice($userline, 0, $pos > 0 ? $pos : 0),
                            array_slice($userline, self::$inputValues['position']-1)
                        );
                        self::$inputValues['prompt'] = join($userline);
                        self::$inputValues['position'] = self::$inputValues['position']-1;
                        self::drawInput();
                    }
                    break;
                case KEY_LEFT:
                    if (count($userline)>0 && self::$inputValues['position']>1) {
                        self::$inputValues['position'] = self::$inputValues['position']-1;
                        self::drawInput();
                    }
                    break;
                case KEY_RIGHT:
                    if (count($userline)>0 && self::$inputValues['position']<=count($userline)) {
                        self::$inputValues['position'] = self::$inputValues['position']+1;
                        self::drawInput();
                    }
                    break;
                case KEY_DELETE:
                    if (count($userline)>0 && self::$inputValues[3]<count($userline)+1) {
                        $pos = self::$inputValues['position']-1;
                        $userline = array_merge(
                            array_slice($userline, 0, $pos > 0 ? $pos : 0),
                            array_slice($userline, self::$inputValues['position'])
                        );
                        self::$inputValues['prompt'] = join($userline);
                        self::drawInput();
                    }
                    break;
                case KEY_TAB:
                case KEY_SHIFT_TAB:
                    break;
                case chr(27).chr(91).chr(50).chr(126):
                case chr(4):
                    // Insert key
                    break;
                case KEY_CTRL_C:
                case chr(27):
                    // Escape key
                    self::drawInputCancelled();
                    die();
                default:
                    if (count($userline) < (self::$cols - 7)) {
                        //$userline[] = $input;
                        $userline = array_merge(
                            array_slice($userline, 0, self::$inputValues['position']-1),
                            array($input),
                            array_slice($userline, self::$inputValues['position']-1)
                        );
                        $val = '';
                        self::$inputValues['prompt'] = join($userline);
                        self::$inputValues['position'] = self::$inputValues['position']+1;
                        self::drawInput();
                    }
                    break;
            }
        }
    
        self::restoreTty();
        printf("\n");

        return join($userline);
    }

    public static function get_user_choice($title, $values, $default = null, $scroll = 5, $validate = null, $hint = '')
    {
        self::checkStart();

        $def = 0;

        if (!is_assoc($values)) {
            $realvalues = array();
            foreach ($values as $val) {
                $realvalues[$val] = $val;
            }
        } else {
            $realvalues = $values;
        }


        if ($default) {
            $def = is_string($default)? array_search($default, array_keys($values)) : $default;
        }
        
        $scroll = count($values)>$scroll ? $scroll : count($values);

        $values = array_values($realvalues);
        $showed = array_slice($values, 0, $scroll);

        if ($def > 0) {
            $next = $scroll;
            while (!in_array($values[$def], $showed)) {
                array_shift($showed);
                $showed = array_merge($showed, array($values[$next]));
                $next++;
            }
        }

        self::$inputValues = array(
            'title' => $title,
            'values' =>  $values, 
            'showed' => $showed, 
            'selected' => $def,
            'scroll' => $scroll,
            'realvalues' => $realvalues,
            'hint' => $hint,
            'validate' => $validate
        );

        self::drawChoice();

        self::hideCursor();

        while (true) {
            $input = self::waitForInput();

            switch($input){
                case KEY_ENTER:
                    if ($validate) {
                        list($class, $method) = getCallbackFromString($validate);
                        $s = array_keys($realvalues);
                        $res = executeCallback($class, $method, array($s[self::$inputValues['selected']]));
                        if ($res) {
                            self::drawChoiceRequired($res);
                            break;
                        } else {
                            self::drawChoiceSelected();
                            break 2;
                        }
                    } else {
                        self::drawChoiceSelected();
                        break 2;
                    }
                case KEY_UP:
                case KEY_SHIFT_TAB:
                    self::drawChoice("UP");
                    break;
                case KEY_DOWN:
                case KEY_TAB:
                    self::drawChoice("DOWN");
                    break;
                case KEY_CTRL_C:
                case chr(27):
                    self::drawChoiceCancelled();
                    die();
                default:
                    break;
            }
        }
    
        self::restoreTty();
        
        $selected = array_keys($realvalues);
        return $selected[self::$inputValues['selected']];

    }

    public static function get_user_multichoice($title, $values, $default = array(), $scroll = 5, $required = false, $validate = null, $hint = '')
    {
        self::checkStart();

        $multisel = is_array($default) ? $default : array();

        if (!is_assoc($values)) {
            $realvalues = array();
            foreach ($values as $val) {
                $realvalues[$val] = $val;
            }
        } else {
            $realvalues = $values;
        }

        $values = array_values($values);
        $showed = array_slice($values, 0, $scroll);
        $scroll = count($values)<$scroll ? count($values) : $scroll;

        self::$inputValues = array(
            'title' => $title,
            'values' =>  $values, 
            'showed' => $showed, 
            'selected' => 0,
            'scroll' => $scroll,
            'multisel' => $multisel,
            'realvalues' => $realvalues,
            'hint' => $hint,
            'required' => $required===true ? 'Required.' : $required,
        );

        self::drawMultichoice();

        self::hideCursor();

        while (true) {
            $input = self::waitForInput();

            switch($input){
                case KEY_ENTER:
                    if ($required!==false && count(self::$inputValues['multisel'])==0) {
                        self::drawMultichoiceRequired();
                        break;
                    } elseif ($validate) {
                        list($class, $method) = getCallbackFromString($validate);
                        $res = executeCallback($class, $method, array(self::$inputValues['multisel']));
                        if ($res) {
                            self::drawMultichoiceRequired($res);
                            break;
                        } else {
                            self::drawMultichoiceSelected();
                            break 2;
                        }
                    } else {
                        self::drawMultichoiceSelected();
                        break 2;
                    }
                case KEY_UP:
                case KEY_SHIFT_TAB:
                    self::drawMultichoice('UP');
                    break;
                case KEY_DOWN:
                case KEY_TAB:
                    self::drawMultichoice('DOWN');
                    break;
                case KEY_SPACE:
                    self::drawMultichoice('SELECT');
                    break;
                case KEY_CTRL_C:
                case chr(27):
                    self::drawMultichoiceCancelled();
                    die();
                default:
                    break;
            }
        }
    
        self::restoreTty();
        
        return self::$inputValues['multisel'];
    }

    public static function get_user_confirm($title, $default=true, $yes='Yes', $no='No', $required=false, $hint = '')
    {
        self::checkStart();
        
        self::$inputValues = array(
            'title' => $title,
            'selected' =>  $default? 0 : 1, 
            'yes' => $yes,
            'no' => $no,
            'hint' => $hint,
            'required' => $required
        );

        self::drawConfirm();

        self::hideCursor();

        $userline = array();
        
        while (true) {
            $input = self::waitForInput();

            switch($input) {
                case chr(116):
                case chr(121):
                    self::$inputValues['selected'] = 0;
                    self::drawConfirm();
                    break;
                case chr(89):
                case chr(110):
                    self::$inputValues['selected'] = 1;
                    self::drawConfirm();
                    break;
                case KEY_ENTER:
                    if (self::$inputValues['selected']==1 && $required!==false) {
                        self::drawConfirmRequired();
                        break;
                    } else {
                        self::drawConfirmSelected();
                        break 2;
                    }
                case KEY_LEFT:
                case KEY_RIGHT:
                case KEY_TAB:
                case KEY_SHIFT_TAB:
                    self::drawConfirm('CHANGE');
                    break;
                case KEY_CTRL_C:
                case chr(27):
                    self::drawConfirmCancelled();
                    die();
                default:
                    self::$inputValues['title'] = ord($input);
                    break;
            }
        }
    
        self::restoreTty();
        printf("\n");

        return self::$inputValues['selected']==0;
    }

    public static function get_user_suggest($title, $values, $placeholder = '', $default = '', $scroll = 5, $required = false, $validate = null, $hint = '')
    {
        self::checkStart();

        $def = 0;

        if (!is_assoc($values)) {
            $realvalues = array();
            foreach ($values as $val) {
                $realvalues[$val] = $val;
            }
        } else {
            $realvalues = $values;
        }
        
        $scroll = count($values)>$scroll ? $scroll : count($values);

        $values = array_values($realvalues);
        $showed = array_slice($values, 0, $scroll);

        if ($def > 0) {
            $next = $scroll;
            while (!in_array($values[$def], $showed)) {
                array_shift($showed);
                $showed = array_merge($showed, array($values[$next]));
                $next++;
            }
        }

        $userline = $default=='' ? array() : str_split($default);

        self::$inputValues = array(
            'title' => $title,
            'placeholder' => $placeholder,
            'hint' => $hint,
            'values' =>  $values, 
            'realvalues' => $realvalues,
            'prompt' => $default,
            'showed' => $showed, 
            'selected' => -1,
            'scroll' => $scroll,
            'position' => 1,
            'showoptions' => false,
            'required' => $required===true ? 'Required.' : $required,
            'current' => null,
            'lastprompt' => '',
            'lastfilter' => $values
        );

        self::drawSuggest();

        self::hideCursor();

        while (true) {
            $input = self::waitForInput();

            switch($input){
                case KEY_ENTER:
                    if (!self::$inputValues['current'] &&  $required!==false) {
                        self::drawSuggestRequired();
                        break;
                    } elseif ($validate) {
                        list($class, $method) = getCallbackFromString($validate);
                        $res = executeCallback($class, $method, array(self::$inputValues['current']));
                        if ($res) {
                            self::drawSuggestRequired($res);
                            break;
                        } else {
                            self::drawSuggestSelected();
                            break 2;
                        }
                    } else {
                        self::drawSuggestSelected();
                        break 2;
                    }
                case KEY_UP:
                case KEY_SHIFT_TAB:
                    self::drawSuggest("UP");
                    break;
                case KEY_TAB:
                case KEY_DOWN:
                    self::drawSuggest("DOWN");
                    break;
                case KEY_CTRL_C:
                case chr(27):
                    self::drawSuggestCancelled();
                    die();
                case KEY_BACKSPACE:
                    if (count($userline)>0) {
                        $pos = self::$inputValues['position']-2;
                        $userline = array_merge(
                            array_slice($userline, 0, $pos > 0 ? $pos : 0),
                            array_slice($userline, self::$inputValues['position']-1)
                        );
                        self::$inputValues['prompt'] = join($userline);
                        self::$inputValues['position'] = self::$inputValues['position']-1;
                        if (count($userline)==0) {
                            self::$inputValues['selected'] = -1;
                            self::$inputValues['showoptions'] = false;
                        }
                        self::drawSuggest();
                    }
                    break;
                case KEY_LEFT:
                    if (count($userline)>0 && self::$inputValues['position']>1) {
                        self::$inputValues['position'] = self::$inputValues['position']-1;
                        self::drawSuggest();
                    }
                    break;
                case KEY_RIGHT:
                    if (count($userline)>0 && self::$inputValues['position']<=count($userline)) {
                        self::$inputValues['position'] = self::$inputValues['position']+1;
                        self::drawSuggest();
                    }
                    break;
                case KEY_DELETE:
                    if (count($userline)>0 && self::$inputValues[3]<count($userline)+1) {
                        $pos = self::$inputValues['position']-1;
                        $userline = array_merge(
                            array_slice($userline, 0, $pos > 0 ? $pos : 0),
                            array_slice($userline, self::$inputValues['position'])
                        );
                        self::$inputValues['prompt'] = join($userline);
                        if (count($userline)==0) {
                            self::$inputValues['selected'] = -1;
                            self::$inputValues['showoptions'] = false;
                        }
                        self::drawSuggest();
                    }
                    break;
                default:
                    if (count($userline) < (self::$cols - 7)) {
                        //$userline[] = $input;
                        $userline = array_merge(
                            array_slice($userline, 0, self::$inputValues['position']-1),
                            array($input),
                            array_slice($userline, self::$inputValues['position']-1)
                        );
                        $val = '';
                        self::$inputValues['prompt'] = join($userline);
                        self::$inputValues['position'] = self::$inputValues['position']+1;
                        self::drawSuggest();
                    }
                    break;
            }
        }
    
        self::restoreTty();
        
        printf("\n");

        return self::$inputValues['current'];

    }

    public static function get_user_search($title, $callback, $placeholder = '', $scroll = 5, $validate = null, $hint = '')
    {
        self::checkStart();

        $userline = array();

        self::$inputValues = array(
            'title' => $title,
            'placeholder' => $placeholder,
            'hint' => $hint,
            'callback' =>  $callback, 
            'required' => 'Required.',
            'prompt' => '',
            'selected' => -1,
            'scroll' => $scroll,
            'position' => 1,
            'showoptions' => false,
            'current' => null,
            'lastprompt' => '',
            'lastfilter' => array(),
            'lastrealfilter' => array(),
            'showed' => array()
        );

        self::drawSearch();

        self::hideCursor();

        while (true) {
            $input = self::waitForInput();

            switch($input){
                case KEY_ENTER:
                    if (!self::$inputValues['current']) {
                        self::drawSuggestRequired();
                        break;
                    }
                    if ($validate) {
                        list($class, $method) = getCallbackFromString($validate);
                        $res = executeCallback($class, $method, array(self::$inputValues['current']));
                        if ($res) {
                            self::drawSearchRequired($res);
                            break;
                        } else {
                            self::drawSearchSelected();
                            break 2;
                        }
                    } else {
                        self::drawSearchSelected();
                        break 2;
                    }
                case KEY_UP:
                case KEY_SHIFT_TAB:
                    self::drawSearch("UP");
                    break;
                case KEY_TAB:
                case KEY_DOWN:
                    self::drawSearch("DOWN");
                    break;
                case KEY_CTRL_C:
                case chr(27):
                    self::drawSearchCancelled();
                    die();
                case KEY_BACKSPACE:
                    if (count($userline)>0) {
                        $pos = self::$inputValues['position']-2;
                        $userline = array_merge(
                            array_slice($userline, 0, $pos > 0 ? $pos : 0),
                            array_slice($userline, self::$inputValues['position']-1)
                        );
                        self::$inputValues['prompt'] = join($userline);
                        self::$inputValues['position'] = self::$inputValues['position']-1;
                        if (count($userline)==0) {
                            self::$inputValues['selected'] = -1;
                            self::$inputValues['showoptions'] = false;
                        }
                        self::drawSearch();
                    }
                    break;
                case KEY_LEFT:
                    if (count($userline)>0 && self::$inputValues['position']>1) {
                        self::$inputValues['position'] = self::$inputValues['position']-1;
                        self::drawSearch();
                    }
                    break;
                case KEY_RIGHT:
                    if (count($userline)>0 && self::$inputValues['position']<=count($userline)) {
                        self::$inputValues['position'] = self::$inputValues['position']+1;
                        self::drawSearch();
                    }
                    break;
                case KEY_DELETE:
                    if (count($userline)>0 && self::$inputValues[3]<count($userline)+1) {
                        $pos = self::$inputValues['position']-1;
                        $userline = array_merge(
                            array_slice($userline, 0, $pos > 0 ? $pos : 0),
                            array_slice($userline, self::$inputValues['position'])
                        );
                        self::$inputValues['prompt'] = join($userline);
                        if (count($userline)==0) {
                            self::$inputValues['selected'] = -1;
                            self::$inputValues['showoptions'] = false;
                        }
                        self::drawSearch();
                    }
                    break;
                default:
                    if (count($userline) < (self::$cols - 7)) {
                        //$userline[] = $input;
                        $userline = array_merge(
                            array_slice($userline, 0, self::$inputValues['position']-1),
                            array($input),
                            array_slice($userline, self::$inputValues['position']-1)
                        );
                        $val = '';
                        self::$inputValues['prompt'] = join($userline);
                        self::$inputValues['position'] = self::$inputValues['position']+1;
                        self::drawSearch();
                    }
                    break;
            }
        }
    
        self::restoreTty();
        
        printf("\n");

        return self::$inputValues['current'];

    }


    private static function drawInput()
    {
        $title = self::$inputValues['title'];
        $placeholder = self::$inputValues['placeholder'];
        $prompt = self::$inputValues['prompt'];
        $password = self::$inputValues['password'];
        $position = self::$inputValues['position'];
        $hint = self::$inputValues['hint'];

        $output = array();

        $text = array();
        foreach (str_split($prompt) as $char) {
            if (ord($char)!=127) $text[] = $char;
        }
        $prompt = join($text);

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;240m │ \033[m";

        if (strlen($prompt)==0) {
            if (strlen($placeholder)>0) {
                $input .= "\033[7;49;38m".$placeholder[0]."\033[m\033[38;5;248m".substr($placeholder, 1)."\033[m";
                $spaces = self::$cols - 6 - strlen($placeholder);
            } else {
                $input .= "\033[7;49;39m \033[m";
                $spaces = self::$cols - 7;
            }
        } else {
            $message = $password ? str_repeat('*', strlen($prompt)) : $prompt;
            
            $i = 1;
            foreach (str_split($message) as $letter) {
                if ($i==$position) {
                    $input .= "\033[7;49;39m".$letter."\033[m";
                } else {
                    $input .= $letter;
                }
                $i++;
            }

            $spaces = self::$cols - 6 - strlen($prompt);

            if ($position>strlen($prompt)) {
                $input .= "\033[7;49;39m \033[m";
                $spaces--;
            }
        }

        $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;
        $output[] = "\033[38;5;240m"./* $position.":".strlen($prompt). */" └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        
        if (strlen($hint) > 0) {
            $output[] = "\033[38;5;240m  $hint\033[m";
        }
        
        self::replaceCommandOutput($output);
    }

    private static function drawInputCancelled()
    {
        $title = self::$inputValues['title'];
        $placeholder = self::$inputValues['placeholder'];
        $prompt = self::$inputValues['prompt'];
        $password = self::$inputValues['password'];

        if ($password) $prompt = str_repeat('*', strlen($prompt));


        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[31m ┌ \033[m" . $title . "\033[31m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[31m │ \033[m";

        if (strlen($prompt)==0) {
            $input .= "\033[9;38;5;248m".$placeholder."\033[m";
            $spaces = self::$cols - 6 - strlen($placeholder);
        } else {
            $input .= "\033[9;38;5;248m".$prompt."\033[m";
            $spaces = self::$cols - 6 - strlen($prompt);
        }
        $input .= "\033[31m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;

        $output[] = "\033[31m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[31m  ⚠ Cancelled.\033[m";

        self::replaceCommandOutput($output);

        printf("\n\n");
        
        self::restoreTty();

    }

    private static function drawInputFinished()
    {
        $title = self::$inputValues['title'];
        $password = self::$inputValues['password'];
        $prompt = self::$inputValues['prompt'];

        $output = array();

        $text = array();
        foreach (str_split($prompt) as $char) {
            if (ord($char)!=127) $text[] = $char;
        }
        $prompt = join($text);

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;248m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;240m │ \033[m" . ($password ? str_repeat('*', strlen($prompt)) : $prompt);
        $spaces = self::$cols - 6 - strlen($prompt);
        $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";
        $output[] = $input;

        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "";

        self::replaceCommandOutput($output);

    }

    private static function drawInputRequired($text = null)
    {
        $title = self::$inputValues['title'];
        $placeholder = self::$inputValues['placeholder'];
        $prompt = self::$inputValues['prompt'];
        $password = self::$inputValues['password'];
        $position = self::$inputValues['position'];
        $required = $text ? $text : self::$inputValues['required'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;172m ┌ \033[m" . $title . "\033[38;5;172m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;172m │ \033[m";

        if (strlen($prompt)==0) {
            if (strlen($placeholder)>0) {
                $input .= "\033[7;49;38m".$placeholder[0]."\033[m\033[38;5;248m".substr($placeholder, 1)."\033[m";
                $spaces = self::$cols - 6 - strlen($placeholder);
            } else {
                $spaces = self::$cols - 6;
            }
        } else {
            $message = $password ? str_repeat('*', strlen($prompt)) : $prompt;
            
            $i = 1;
            foreach (str_split($message) as $letter) {
                if ($i==$position) {
                    $input .= "\033[7;49;39m".$letter."\033[m";
                } else {
                    $input .= $letter;
                }
                $i++;
            }

            $spaces = self::$cols - 6 - strlen($prompt);

            if ($position>strlen($prompt)) {
                $input .= "\033[7;49;39m \033[m";
                $spaces--;
            }
        }

        $input .= "\033[38;5;172m ".str_repeat(' ', $spaces)." │ \033[m";
        $output[] = $input;

        $output[] = "\033[38;5;172m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[38;5;172m  ⚠ $required\033[m";

        self::replaceCommandOutput($output);
    }

    private static function drawChoice($action=null)
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $selected = self::$inputValues['selected'];
        $showed = self::$inputValues['showed'];
        $scroll = self::$inputValues['scroll'];
        $hint = self::$inputValues['hint'];

        if ($action=='UP') {
            $selected--;
        } elseif ($action=='DOWN') {
            $selected++;
        }
        
        if ($selected < 0) $selected = count($values) -1;
        if ($selected > (count($values) -1)) $selected = 0;

        if ($action=='UP') {
            if ($selected == count($values)-1) {
                $showed = array_slice($values, $selected-($scroll-1), $scroll);
            }
            if (!in_array($values[$selected], $showed)) {
                array_pop($showed);
                $showed = array_merge(array($values[$selected]), $showed);
            }
        }

        if ($action=='DOWN') {
            if ($selected == 0) {
                $showed = array_slice($values, 0, $scroll);
            }
            if (!in_array($values[$selected], $showed)) {
                array_shift($showed);
                $showed[] = $values[$selected];
            }
        }

        while (count($showed) > $scroll) {
            array_pop($showed);
        }

        self::$inputValues['selected'] = $selected;
        self::$inputValues['showed'] = $showed;

        $output = array();

        $bar = self::getScrollbar($values, $showed, $scroll);

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;6m" . $title /* .":".$bar  */. "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $count = 0; $real = 0;
        foreach ($values as $option) {
            if (in_array($option, $showed)) {
                $input = "\033[38;5;240m │ \033[m";
                if ($real==$selected) {
                    $input .= "\033[38;5;6m› ●  \033[m".$option."";
                } else {
                    $input .= "\033[38;5;248m  ○  ".$option."\033[m";
                }
                $spaces = self::$cols - 11 - strlen($option) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= " │ \033[m";    
                $output[] = $input;                
                $count++;
            }
            $real++;
            if ($count==$scroll) break;
        }

        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        
        if (strlen($hint) > 0) {
            $output[] = "\033[38;5;240m  $hint\033[m";
        }
        
        self::replaceCommandOutput($output);
    }

    private static function drawChoiceRequired($required)
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $selected = self::$inputValues['selected'];
        $showed = self::$inputValues['showed'];
        $scroll = self::$inputValues['scroll'];

        $output = array();

        $bar = self::getScrollbar($values, $showed, $scroll, false);

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;172m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;172m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $count = 0; $real = 0;
        foreach ($values as $option) {
            if (in_array($option, $showed)) {
                $input = "\033[38;5;172m │ \033[m";
                if ($real==$selected) {
                    $input .= "\033[38;5;6m› ●  \033[m".$option."";
                } else {
                    $input .= "\033[38;5;248m  ○  ".$option."\033[m";
                }
                $spaces = self::$cols - 11 - strlen($option) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= "\033[38;5;172m │ \033[m";    
                $output[] = $input;                
                $count++;
            }
            $real++;
            if ($count==$scroll) break;
        }

        $output[] = "\033[38;5;172m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[38;5;172m  ⚠ $required\033[m";
        
        self::replaceCommandOutput($output);
    }

    private static function drawChoiceCancelled()
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $selected = self::$inputValues['selected'];
        $showed = self::$inputValues['showed'];
        $scroll = self::$inputValues['scroll'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[31m ┌ \033[m" . $title . "\033[31m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;
    
        $bar = self::getScrollbar($values, $showed, $scroll, false);

        $count = 0; $real = 0;
        foreach ($showed as $option) {
            if (in_array($option, $showed)) {
                $input = "\033[31m │ \033[m";
                if ($count==$selected) {
                    $input .= "\033[38;5;248m› ●  \033[9;38;5;248m".$option."\033[m";
                } else {
                    $input .= "\033[38;5;248m  ○  \033[9;38;5;248m".$option."\033[m";
                }
                $spaces = self::$cols - 11 - strlen($option) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= "\033[31m │ \033[m";   
                /* $spaces = 54 - strlen($option);
                $input .= "\033[31m ".str_repeat(' ', $spaces)." │ \033[m"; */    
                $output[] = $input;
                $count++;
            }
            $real++;
            if ($count==$scroll) break;
        }

        $output[] = "\033[31m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[31m  ⚠ Cancelled.\033[m";
        
        self::replaceCommandOutput($output);

        self::restoreTty();

        printf("\n\n");
    }

    private static function drawChoiceSelected()
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $selected = self::$inputValues['selected'];
        $showed = self::$inputValues['showed'];

        $value = $values[$selected];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;248m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;
    
        $input = "\033[38;5;240m │ \033[m";
        $input .= $value;
        $spaces = self::$cols - 6 - strlen($value);
        $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";    
        $output[] = $input;

        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "";
        
        self::replaceCommandOutput($output);

        /* for ($i=0; $i<count($showed)-1; $i++){
            echo "\033[1A";
            echo "\r";
            echo "\033[K";
        } */

        printf("\n");

    }

    private static function drawMultichoice($action=null)
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $selected = self::$inputValues['selected'];
        $showed = self::$inputValues['showed'];
        $scroll = self::$inputValues['scroll'];
        $multisel = self::$inputValues['multisel'];
        $realvalues = self::$inputValues['realvalues'];
        $hint = self::$inputValues['hint'];

        if ($action=='UP') {
            $selected--;
        }

        if ($action=='DOWN') {
            $selected++;
        }

        if ($action=='SELECT') {
            $s = array_keys($realvalues);

            if (in_array($s[$selected], $multisel)) {
                $multisel = array_diff($multisel, array($s[$selected])); 
            } else {
                $multisel[] = $s[$selected];
            }
        }



        if ($selected < 0) $selected = count($values) -1;
        if ($selected > (count($values)-1)) $selected = 0;

        if ($action=='UP') {
            if ($selected == count($values)-1) {
                $showed = array_slice($values, $selected-($scroll-1), $scroll);
            }
            if (!in_array($values[$selected], $showed)) {
                array_pop($showed);
                $showed = array_merge(array($values[$selected]), $showed);
            }
        }

        if ($action=='DOWN') {
            if ($selected == 0) {
                $showed = array_slice($values, 0, $scroll);
            }
            if (!in_array($values[$selected], $showed)) {
                array_shift($showed);
                $showed[] = $values[$selected];
            }
        }

        while (count($showed) > $scroll) {
            array_pop($showed);
        }

        self::$inputValues['selected'] = $selected;
        self::$inputValues['showed'] = $showed;
        self::$inputValues['multisel'] = $multisel;


        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $bar = self::getScrollbar($values, $showed, $scroll);

        $count = 0; $real = 0;
        foreach ($realvalues as $key => $value) {
            if (in_array($value, $showed)) {
                
                $input = "\033[38;5;240m │ \033[m";
                if ($real==$selected) {
                    $input .= "\033[38;5;6m›\033[m" .
                        (in_array($key, $multisel) ? "\033[38;5;6m ◼  \033[m" : " ◻  \033[m") .
                        $value;
                } else {
                    $input .= "\033[38;5;240m " .
                        (in_array($key, $multisel) ? "\033[38;5;6m ◼  \033[m" : "\033[38;5;248m ◻  \033[m") .
                        "\033[38;5;248m" . $value . "\033[m";
                }
                $spaces = self::$cols - 11 - strlen($value) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= " │ \033[m";    
                $output[] = $input;                
                $count++;
            }
            $real++;
            if ($count==$scroll) break;
        }

        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        
        if (strlen($hint) > 0) {
            $output[] = "\033[38;5;240m  $hint\033[m";
        }
        
        self::replaceCommandOutput($output);
    }

    private static function drawMultichoiceRequired($text=null)
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $selected = self::$inputValues['selected'];
        $showed = self::$inputValues['showed'];
        $scroll = self::$inputValues['scroll'];
        $multisel = self::$inputValues['multisel'];
        $required = $text ? $text : self::$inputValues['required'];
        $realvalues = self::$inputValues['realvalues'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;172m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;172m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $bar = self::getScrollbar($values, $showed, $scroll, false);

        $count = 0; $real = 0;
        foreach ($realvalues as $key => $value) {
            if (in_array($value, $showed)) {
                
                $input = "\033[38;5;172m │ \033[m";
                if ($real==$selected) {
                    $input .= "\033[38;5;6m›\033[m" .
                        (in_array($key, $multisel) ? "\033[38;5;6m ◼  \033[m" : " ◻  \033[m") .
                        $value;
                } else {
                    $input .= "\033[38;5;240m " .
                        (in_array($key, $multisel) ? "\033[38;5;6m ◼  \033[m" : "\033[38;5;248m ◻  \033[m") .
                        "\033[38;5;248m" . $value . "\033[m";
                }
                $spaces = self::$cols - 11 - strlen($value) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= "\033[38;5;172m │ \033[m";    
                $output[] = $input;                
                $count++;
            }
            $real++;
            if ($count==$scroll) break;
        }

        $output[] = "\033[38;5;172m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[38;5;172m  ⚠ $required\033[m";
        
        self::replaceCommandOutput($output);
    }

    private static function drawMultichoiceCancelled()
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $showed = self::$inputValues['showed'];
        $scroll = self::$inputValues['scroll'];
        $multisel = self::$inputValues['multisel'];
        $selected = self::$inputValues['selected'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[31m ┌ \033[m" . $title . "\033[31m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;
    
        $bar = self::getScrollbar($values, $showed, $scroll, false);

        $count = 0; $real = 0;
        foreach ($values as $option) {
            if (in_array($option, $showed)) {
                $input = "\033[31m │ \033[m";
                $input .= "\033[38;5;248m" . ($real==$selected? "›" : " ") .
                    (in_array($option, $multisel) ? " ◼  " : " ◻  ") .
                    "\033[9;38;5;248m" . $option . "\033[m";
                $spaces = self::$cols - 11 - strlen($option) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= "\033[31m │ \033[m";
                $output[] = $input;                
                $count++;
            }
            $real++;
            if ($count==$scroll) break;
        }

        $output[] = "\033[31m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[31m  ⚠ Cancelled.\033[m";
        
        self::replaceCommandOutput($output);

        self::restoreTty();

        printf("\n\n");
    }

    private static function drawMultichoiceSelected()
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $scroll = self::$inputValues['scroll'];
        $multisel = self::$inputValues['multisel'];
        $realvalues = self::$inputValues['realvalues'];
        
        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;248m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;
    
        $count = 0; 
        foreach ($realvalues as $key => $value) {
            if (in_array($key, $multisel)) { 
                $input = "\033[38;5;240m │ \033[m" . $value;
                $spaces = self::$cols - 6 - strlen($value);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";    
                $output[] = $input;
            }
            $count++;
        }

        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        //$output[] = str_repeat("\n", abs($scroll - count($multisel)));
        
        self::replaceCommandOutput($output);

        printf("\n\n");

        /* for ($i=0; $i<abs($scroll - count($multisel)); $i++){
            echo "\033[1A";
            echo "\r";
            echo "\033[K";
        } */

    }

    private static function drawConfirm($action=null)
    {
        $title = self::$inputValues['title'];
        $selected = self::$inputValues['selected'];
        $yes = self::$inputValues['yes'];
        $no = self::$inputValues['no'];
        $hint = self::$inputValues['hint'];

        if ($action=='CHANGE') {
            $selected = $selected==0? 1 : 0;
            self::$inputValues['selected'] = $selected;
        }

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;240m │\033[m";
        if ($selected==0) {
            $input .= "\033[38;5;6m ●  \033[m".$yes."";
        } else {
            $input .= "\033[38;5;240m ○  ".$yes."\033[m";
        }
        $input .= "\033[38;5;240m /\033[m";

        if ($selected==1) {
            $input .= "\033[38;5;6m ●  \033[m".$no."";
        } else {
            $input .= "\033[38;5;240m ○  ".$no."\033[m";
        }

        $spaces = self::$cols - 15 - strlen($yes) - strlen($no);
        $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;
        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        
        if (strlen($hint) > 0) {
            $output[] = "\033[38;5;240m  $hint\033[m";
        }
        
        self::replaceCommandOutput($output);
    }

    private static function drawConfirmRequired()
    {
        $title = self::$inputValues['title'];
        $selected = self::$inputValues['selected'];
        $yes = self::$inputValues['yes'];
        $no = self::$inputValues['no'];
        $required = self::$inputValues['required'];

        if ($required===true) {
            $required = 'Required.';
        }

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;172m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;172m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;172m │\033[m";
        if ($selected==0) {
            $input .= "\033[38;5;6m ●  \033[m".$yes."";
        } else {
            $input .= "\033[38;5;240m ○  ".$yes."\033[m";
        }
        $input .= "\033[38;5;240m /\033[m";

        if ($selected==1) {
            $input .= "\033[38;5;6m ●  \033[m".$no."";
        } else {
            $input .= "\033[38;5;240m ○  ".$no."\033[m";
        }

        $spaces = self::$cols - 15 - strlen($yes) - strlen($no);
        $input .= "\033[38;5;172m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;
        $output[] = "\033[38;5;172m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[38;5;172m  ⚠ $required\033[m";
        
        self::replaceCommandOutput($output);
    }

    private static function drawConfirmCancelled()
    {
        $title = self::$inputValues['title'];
        $selected = self::$inputValues['selected'];
        $yes = self::$inputValues['yes'];
        $no = self::$inputValues['no'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[31m ┌ \033[m" . $title . "\033[31m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[31m │\033[m";

        if ($selected==0) {
            $input .= "\033[38;5;248m ●  \033[9;38;5;248m".$yes."\033[m";
        } else {
            $input .= "\033[38;5;248m ○  \033[9;38;5;248m".$yes."\033[m";
        }

        if ($selected==1) {
            $input .= "\033[38;5;248m ●  \033[9;38;5;248m".$no."\033[m";
        } else {
            $input .= "\033[38;5;248m ○  \033[9;38;5;248m".$no."\033[m";
        }

        $spaces = self::$cols - 13 - strlen($yes) - strlen($no);
        $input .= "\033[31m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;
        $output[] = "\033[31m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[31m  ⚠ Cancelled.\033[m";
        
        self::replaceCommandOutput($output);

        self::restoreTty();

        printf("\n\n");
    }

    private static function drawConfirmSelected()
    {
        $title = self::$inputValues['title'];
        $selected = self::$inputValues['selected'];
        $yes = self::$inputValues['yes'];
        $no = self::$inputValues['no'];

        $value = $selected==0? $yes : $no;

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;248m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;240m │ \033[m" . $value;
        $spaces = self::$cols - 6 - strlen($value);
        $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;
        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "                        ";
        
        self::replaceCommandOutput($output);
    }

    private static function drawSuggest($action = null)
    {
        $title = self::$inputValues['title'];
        $prompt = self::$inputValues['prompt'];
        $placeholder = self::$inputValues['placeholder'];
        $position = self::$inputValues['position'];
        $selected = self::$inputValues['selected'];
        $values = self::$inputValues['values'];
        $scroll = self::$inputValues['scroll'];
        $showoptions = self::$inputValues['showoptions'];
        $lastprompt = self::$inputValues['lastprompt'];
        $lastfilter = self::$inputValues['lastfilter'];
        $showed = self::$inputValues['showed'];
        $hint = self::$inputValues['hint'];

        $output = array();

        $text = array();
        foreach (str_split($prompt) as $char) {
            if (ord($char)!=127) $text[] = $char;
        }
        $prompt = join($text);


        // Filter options
        $filtered = array();

        if (strlen($prompt)>0 && $prompt!=$lastprompt && count($values)>0) {
            foreach ($values as $value) {
                if (strpos(strtolower($value), strtolower($prompt))===0) {
                    $filtered[] = $value;
                }
            }
            $selected = -1;
            $showed = array_slice($filtered, 0, $scroll);
            $showoptions = true;
        } else {
            $filtered = strlen($prompt)>0 ? $lastfilter : $values;
        }

        if (count($filtered)>0) {
            
            if ($action=='UP') {
                $selected--;

                if ($selected==-2 && !$showoptions && strlen($prompt)==0) {
                    $selected = count($filtered) -1;
                    $showoptions = true;
                }

                elseif (strlen($prompt)==0 && $showoptions && $selected==-1) {
                    $showoptions = false;
                }

                elseif (strlen($prompt)>0 && $showoptions && $selected==-2) {
                    $selected = count($filtered) -1;
                }

            } 

            if ($action=='DOWN') {
                $selected++;
                $showoptions = true;

                if ($selected > (count($filtered) -1)) {
                    $selected = -1;
                    if (strlen($prompt)==0) $showoptions = false;
                }
            }

            if ($action=='UP' && $selected > -1) {
                if ($selected == count($filtered)-1) {
                    $showed = array_slice($filtered, ($selected<0?0:$selected)-($scroll-1), $scroll);
                }
                if (!in_array($filtered[$selected], $showed)) {
                    array_pop($showed);
                    $showed = array_merge(array($filtered[$selected]), $showed);
                }
            }

            if ($action=='DOWN' && $selected > -1) {
                if ($selected == 0) {
                    $showed = array_slice($filtered, 0, $scroll);
                }
                if (!in_array($filtered[$selected], $showed)) {
                    array_shift($showed);
                    $showed[] = $filtered[$selected];
                }
            }

            while (count($showed) > $scroll) {
                array_pop($showed);
            }
        } 
        else 
        {
            $selected = -1;
            $showoptions = false;
        }

    
        self::$inputValues['selected'] = $selected;
        self::$inputValues['showoptions'] = $showoptions;
        self::$inputValues['lastprompt'] = $prompt;
        self::$inputValues['lastfilter'] = $filtered;
        self::$inputValues['showed'] = $showed;

        if ($showoptions && $selected>-1 && count($filtered)>0) {
            self::$inputValues['current'] = $filtered[$selected];
        } else {
            self::$inputValues['current'] = strlen($prompt) > 0 ? $prompt : null;
        }

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;240m │ \033[m";

        if (strlen($prompt)==0) {
            if (strlen($placeholder)>0) {
                $input .= ($selected<0 ? "\033[7;49;38m" : "\033[38;5;248m").$placeholder[0]."\033[m\033[38;5;248m".substr($placeholder, 1)."\033[m";
                $spaces = self::$cols - 6 - strlen($placeholder);
            } else {
                $input .= ($selected<0 ? "\033[7;49;38m" : "\033[38;5;248m") . " \033[m";
                $spaces = self::$cols - 7;
            }
        } else {
            $i = 1;
            foreach (str_split($prompt) as $letter) {
                if ($i==$position && $selected<0) {
                    $input .= "\033[7;49;39m".$letter."\033[m";
                } else {
                    $input .= $letter;
                }
                $i++;
            }

            $spaces = self::$cols - 6 - strlen($prompt);

            if ($position>strlen($prompt) && $selected<0) {
                $input .= "\033[7;49;39m \033[m";
                $spaces--;
            }
        }

        if (count($filtered)>0 && strlen($prompt)==0 && !$showoptions) {
            $input .= str_repeat(' ', $spaces);
            $input .= "\033[38;5;6m⌄\033[38;5;240m │ \033[m";
        } else {
            $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";
        }

        $output[] = $input;
        
        if (!$showoptions) {
            $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        } 
        
        // Show options box
        else {
            $output[] = "\033[38;5;240m ├" . str_repeat('─', self::$cols-3) . "┤\033[m";

            $bar = self::getScrollbar($filtered, $showed, $scroll);

            /* $count = 0;
            foreach ($filtered as $option) {
                $input = "\033[38;5;240m │ \033[m";
                if ($count==$selected) {
                    $input .= "\033[38;5;6m› \033[m".$option."";
                } else {
                    $input .= "\033[38;5;248m  ".$option."\033[m";
                }
                $spaces = self::$cols - 8 - strlen($option) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= " │ \033[m";    
                $output[] = $input;                
                $count++;
            } */
            $count = 0; $real = 0;
            foreach ($filtered as $option) {
                if (in_array($option, $showed)) {
                    $input = "\033[38;5;240m │ \033[m";
                    if ($real==$selected) {
                        $input .= "\033[38;5;6m› \033[m".$option."";
                    } else {
                        $input .= "\033[38;5;248m  ".$option."\033[m";
                    }
                    $spaces = self::$cols - 8 - strlen($option) - ($bar? 2 : 0);
                    $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                    if ($bar) {
                        $input .= $bar[$count];
                    }
                    $input .= " │ \033[m";    
                    $output[] = $input;                
                    $count++;
                }
                $real++;
                if ($count==$scroll) break;
            }

            $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        }

        if (strlen($hint) > 0) {
            $output[] = "\033[38;5;240m  $hint\033[m";
        }

        self::replaceCommandOutput($output);
    }

    private static function drawSuggestCancelled()
    {
        $title = self::$inputValues['title'];
        $placeholder = self::$inputValues['placeholder'];
        $prompt = self::$inputValues['prompt'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[31m ┌ \033[m" . $title . "\033[31m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[31m │ \033[m";

        if (strlen($prompt)==0) {
            $input .= "\033[9;38;5;248m".$placeholder."\033[m";
            $spaces = self::$cols - 6 - strlen($placeholder);
        } else {
            $input .= "\033[9;38;5;248m".$prompt."\033[m";
            $spaces = self::$cols - 6 - strlen($prompt);
        }
        $input .= "\033[31m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;

        $output[] = "\033[31m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[31m  ⚠ Cancelled.\033[m";

        self::replaceCommandOutput($output);
        
        printf("\n\n");

        self::restoreTty();

    }

    private static function drawSuggestSelected()
    {
        $title = self::$inputValues['title'];
        $values = self::$inputValues['values'];
        $selected = self::$inputValues['selected'];
        $prompt = self::$inputValues['current'];
        $selected = self::$inputValues['selected'];
        $showoptions = self::$inputValues['showoptions'];

        //$value = $showoptions ? $values[$selected] : $prompt;

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;248m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;
    
        $input = "\033[38;5;240m │ \033[m";
        $input .= $prompt;
        $spaces = self::$cols - 6 - strlen($prompt);
        $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";    
        $output[] = $input;

        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        //$output[] = str_repeat("\n", count($showed)-1);
        
        self::replaceCommandOutput($output);

        printf("\n");

        /* for ($i=0; $i<count($showed)-1; $i++){
            echo "\033[1A";
            echo "\r";
            echo "\033[K";
        } */

    }

    private static function drawSuggestRequired($text = null)
    {
        $title = self::$inputValues['title'];
        $placeholder = self::$inputValues['placeholder'];
        $prompt = self::$inputValues['prompt'];
        $position = self::$inputValues['position'];
        $required = $text ? $text : self::$inputValues['required'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;172m ┌ \033[m" . $title . "\033[38;5;172m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;172m │ \033[m";

        if (strlen($prompt)==0) {
            if (strlen($placeholder)>0) {
                $input .= "\033[7;49;38m".$placeholder[0]."\033[m\033[38;5;248m".substr($placeholder, 1)."\033[m";
                $spaces = self::$cols - 6 - strlen($placeholder);
            } else {
                $spaces = self::$cols - 6;
            }
        } else {
            $i = 1;
            foreach (str_split($prompt) as $letter) {
                if ($i==$position) {
                    $input .= "\033[7;49;39m".$letter."\033[m";
                } else {
                    $input .= $letter;
                }
                $i++;
            }

            $spaces = self::$cols - 6 - strlen($prompt);

            if ($position>strlen($prompt)) {
                $input .= "\033[7;49;39m \033[m";
                $spaces--;
            }
        }

        $input .= "\033[38;5;172m ".str_repeat(' ', $spaces)." │ \033[m";
        $output[] = $input;

        $output[] = "\033[38;5;172m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[38;5;172m  ⚠ $required\033[m";

        self::replaceCommandOutput($output);
    }

    private static function drawSearch($action = null)
    {
        $title = self::$inputValues['title'];
        $prompt = self::$inputValues['prompt'];
        $placeholder = self::$inputValues['placeholder'];
        $position = self::$inputValues['position'];
        $selected = self::$inputValues['selected'];
        $scroll = self::$inputValues['scroll'];
        $showoptions = self::$inputValues['showoptions'];
        $lastprompt = self::$inputValues['lastprompt'];
        $lastfilter = self::$inputValues['lastfilter'];
        $lastrealfilter = self::$inputValues['lastrealfilter'];
        $showed = self::$inputValues['showed'];
        $callback = self::$inputValues['callback'];
        $hint = self::$inputValues['hint'];

        $output = array();

        $text = array();
        foreach (str_split($prompt) as $char) {
            if (ord($char)!=127) $text[] = $char;
        }
        $prompt = join($text);


        // Filter options
        $filtered = array();

        if (strlen($prompt)>0 && $prompt!=$lastprompt) {
            list($class, $method) = getCallbackFromString($callback);
            $lastrealfilter = executeCallback($class, $method, array($prompt));
            $filtered = array_values($lastrealfilter);
            $selected = -1;
            $showed = array_slice($filtered, 0, $scroll);
            $showoptions = true;
        } else {
            $filtered = strlen($prompt)>0 ? $lastfilter : array();
        }

        if (count($filtered)>0) {
            
            if ($action=='UP') {
                $selected--;

                if ($selected==-2 && !$showoptions && strlen($prompt)==0) {
                    $selected = count($filtered) -1;
                    $showoptions = true;
                }

                elseif (strlen($prompt)==0 && $showoptions && $selected==-1) {
                    $showoptions = false;
                }

                elseif (strlen($prompt)>0 && $showoptions && $selected==-2) {
                    $selected = count($filtered) -1;
                }

            } 

            if ($action=='DOWN') {
                $selected++;
                $showoptions = true;

                if ($selected > (count($filtered) -1)) {
                    $selected = -1;
                    if (strlen($prompt)==0) $showoptions = false;
                }
            }

            if ($action=='UP' && $selected > -1) {
                if ($selected == count($filtered)-1) {
                    $showed = array_slice($filtered, ($selected<0?0:$selected)-($scroll-1), $scroll);
                }
                if (!in_array($filtered[$selected], $showed)) {
                    array_pop($showed);
                    $showed = array_merge(array($filtered[$selected]), $showed);
                }
            }

            if ($action=='DOWN' && $selected > -1) {
                if ($selected == 0) {
                    $showed = array_slice($filtered, 0, $scroll);
                }
                if (!in_array($filtered[$selected], $showed)) {
                    array_shift($showed);
                    $showed[] = $filtered[$selected];
                }
            }

            while (count($showed) > $scroll) {
                array_pop($showed);
            }
        } 
        else 
        {
            $selected = -1;
            $showoptions = false;
        }

        self::$inputValues['selected'] = $selected;
        self::$inputValues['showoptions'] = $showoptions;
        self::$inputValues['lastprompt'] = $prompt;
        self::$inputValues['lastfilter'] = $filtered;
        self::$inputValues['lastrealfilter'] = $lastrealfilter;
        self::$inputValues['showed'] = $showed;


        if ($showoptions && $selected>-1 && count($filtered)>0) {
            if (is_assoc($lastrealfilter)) {
                $sel = array_keys($lastrealfilter);
                self::$inputValues['current'] = $sel[$selected];
            } else {
                self::$inputValues['current'] = $lastrealfilter[$selected];
            }
        } else {
            self::$inputValues['current'] = null;
        }
        
        
        $spaces = self::$cols - 5 - strlen($title);
        if ($spaces < 0) $spaces = 0;
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;6m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;240m │ \033[m";

        if (strlen($prompt)==0) {
            if (strlen($placeholder)>0) {
                $input .= ($selected<0 ? "\033[7;49;38m" : "\033[38;5;248m").$placeholder[0]."\033[m\033[38;5;248m".substr($placeholder, 1)."\033[m";
                $spaces = self::$cols - 6 - strlen($placeholder);
            } else {
                $input .= ($selected<0 ? "\033[7;49;38m" : "\033[38;5;248m") . " \033[m";
                $spaces = self::$cols - 7;
            }
        } else {
            $i = 1;
            foreach (str_split($prompt) as $letter) {
                if ($i==$position && $selected<0) {
                    $input .= "\033[7;49;39m".$letter."\033[m";
                } else {
                    $input .= $letter;
                }
                $i++;
            }

            $spaces = self::$cols - 6 - strlen($prompt);

            if ($position>strlen($prompt) && $selected<0) {
                $input .= "\033[7;49;39m \033[m";
                $spaces--;
            }
        }

        if (count($filtered)>0 && strlen($prompt)==0 && !$showoptions) {
            $input .= str_repeat(' ', $spaces);
            $input .= "\033[38;5;6m⌄\033[38;5;240m │ \033[m";
        } else {
            $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";
        }

        $output[] = $input;
        
        if (!$showoptions) {
            $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        } 
        
        // Show options box
        else {
            $output[] = "\033[38;5;240m ├" . str_repeat('─', self::$cols-3) . "┤\033[m";

            $bar = self::getScrollbar($filtered, $showed, $scroll);

            /* $count = 0;
            foreach ($filtered as $option) {
                $input = "\033[38;5;240m │ \033[m";
                if ($count==$selected) {
                    $input .= "\033[38;5;6m› \033[m".$option."";
                } else {
                    $input .= "\033[38;5;248m  ".$option."\033[m";
                }
                $spaces = self::$cols - 8 - strlen($option) - ($bar? 2 : 0);
                $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                if ($bar) {
                    $input .= $bar[$count];
                }
                $input .= " │ \033[m";    
                $output[] = $input;                
                $count++;
            } */
            $count = 0; $real = 0;
            foreach ($filtered as $option) {
                if (in_array($option, $showed)) {
                    $input = "\033[38;5;240m │ \033[m";
                    if ($real==$selected) {
                        $input .= "\033[38;5;6m› \033[m".$option."";
                    } else {
                        $input .= "\033[38;5;248m  ".$option."\033[m";
                    }
                    $spaces = self::$cols - 8 - strlen($option) - ($bar? 2 : 0);
                    $input .= "\033[38;5;240m ".str_repeat(' ', $spaces);
                    if ($bar) {
                        $input .= $bar[$count];
                    }
                    $input .= " │ \033[m";    
                    $output[] = $input;                
                    $count++;
                }
                $real++;
                if ($count==$scroll) break;
            }


            $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        }

        if (strlen($hint) > 0) {
            $output[] = "\033[38;5;240m  $hint\033[m";
        }

        self::replaceCommandOutput($output);
    }

    private static function drawSearchCancelled()
    {
        $title = self::$inputValues['title'];
        $placeholder = self::$inputValues['placeholder'];
        $prompt = self::$inputValues['prompt'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[31m ┌ \033[m" . $title . "\033[31m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[31m │ \033[m";

        if (strlen($prompt)==0) {
            $input .= "\033[9;38;5;248m".$placeholder."\033[m";
            $spaces = self::$cols - 6 - strlen($placeholder);
        } else {
            $input .= "\033[9;38;5;248m".$prompt."\033[m";
            $spaces = self::$cols - 6 - strlen($prompt);
        }
        $input .= "\033[31m ".str_repeat(' ', $spaces)." │ \033[m";

        $output[] = $input;

        $output[] = "\033[31m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[31m  ⚠ Cancelled.\033[m";

        self::replaceCommandOutput($output);
        
        printf("\n\n");

        self::restoreTty();

    }

    private static function drawSearchSelected()
    {
        $title = self::$inputValues['title'];
        $lastrealfilter = self::$inputValues['lastrealfilter'];
        $current = self::$inputValues['current'];

        if (is_assoc($lastrealfilter)) {
            $prompt = $lastrealfilter[$current];
        } else {
            $prompt = self::$inputValues['current'];
        }

        //$value = $showoptions ? $values[$selected] : $prompt;

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;240m ┌ \033[m\033[38;5;248m" . $title . "\033[m\033[38;5;240m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;
    
        $input = "\033[38;5;240m │ \033[m";
        $input .= $prompt;
        $spaces = self::$cols - 6 - strlen($prompt);
        $input .= "\033[38;5;240m ".str_repeat(' ', $spaces)." │ \033[m";    
        $output[] = $input;

        $output[] = "\033[38;5;240m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        //$output[] = str_repeat("\n", count($showed)-1);
        
        self::replaceCommandOutput($output);

        printf("\n");

        /* for ($i=0; $i<count($showed)-1; $i++){
            echo "\033[1A";
            echo "\r";
            echo "\033[K";
        } */

    }

    private static function drawSearchRequired($text = null)
    {
        $title = self::$inputValues['title'];
        $placeholder = self::$inputValues['placeholder'];
        $prompt = self::$inputValues['prompt'];
        $position = self::$inputValues['position'];
        $required = $text ? $text : self::$inputValues['required'];

        $output = array();

        $spaces = self::$cols - 5 - strlen($title);
        $input = "\033[38;5;172m ┌ \033[m" . $title . "\033[38;5;172m " . str_repeat('─', $spaces)."┐\033[m";
        $output[] = $input;

        $input = "\033[38;5;172m │ \033[m";

        if (strlen($prompt)==0) {
            if (strlen($placeholder)>0) {
                $input .= "\033[7;49;38m".$placeholder[0]."\033[m\033[38;5;248m".substr($placeholder, 1)."\033[m";
                $spaces = self::$cols - 6 - strlen($placeholder);
            } else {
                $spaces = self::$cols - 6;
            }
        } else {
            $i = 1;
            foreach (str_split($prompt) as $letter) {
                if ($i==$position) {
                    $input .= "\033[7;49;39m".$letter."\033[m";
                } else {
                    $input .= $letter;
                }
                $i++;
            }

            $spaces = self::$cols - 6 - strlen($prompt);

            if ($position>strlen($prompt)) {
                $input .= "\033[7;49;39m \033[m";
                $spaces--;
            }
        }

        $input .= "\033[38;5;172m ".str_repeat(' ', $spaces)." │ \033[m";
        $output[] = $input;

        $output[] = "\033[38;5;172m └" . str_repeat('─', self::$cols-3) . "┘\033[m";
        $output[] = "\033[38;5;172m  ⚠ $required\033[m";

        self::replaceCommandOutput($output);
    }

}