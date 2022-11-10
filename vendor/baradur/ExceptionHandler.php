<?php

Class ExceptionHandler
{
    private $message;

    private $codes = array(
        100 => 'Connector',
        120 => 'Model'
    );


    public function __construct($exeption)
    {
        $this->message = $exeption;
    }

    public function getMessage()
    {
        return $this->message;
    }

    
    public static function handleException(Exception $ex)
    {
        global $debuginfo;

        //ddd($ex);

        $class = str_replace('.php', '', str_replace('.PHP', '', basename($ex->getFile())));

        $str = '<h3 style="color:red;margin-bottom:0;">Exception Error</h3>
        <hr style="border:0; border-bottom:1px solid lightgray;"><h2 style="margin:0;">'.$ex->getMessage().'</h2>
        <p style="margin:.75rem 0 .35rem 0;">Class</p><strong>'.$class.'</strong><span style="color:teal;margin-left:.55rem;">('.$ex->getFile().')</span><br></p>';

        if ($ex->getCode()==100)
            $str .= '<p style="margin:1rem 0 .25rem 0;">Query</p><p style="color:green;margin-top:.25rem;">'.array_pop($debuginfo['queryes']).'</p>';

        $str .= '<p style="margin:1rem 0 .25rem 0;">Trace</p>';
        foreach ($ex->getTrace() as $trace)
        {
            $args = array();
            foreach ($trace['args'] as $a)
                $args[] = is_object($a)? get_class($a) : 
                    (is_array($a)? '&lt;Array&gt;' : (is_null($a)? '&lt;null&gt;' : $a));
            
            if ($trace['file'])
            {
                $str .= '<p style="margin:0 0 .25rem 0;font-size:.75rem;">'.
                    '<span style="color:green;">['. $trace['line'].']</span><span style="color:gray;margin-left:.35rem;"> ('.
                    $trace['file'].')<span style="margin-left:.5rem;color:blue">';
            }
            else
            {
                $str .= '<p style="color:gray;margin:0 0 .25rem 0;font-size:.75rem;">
                    <span style="color:blue">';
            }

            $str .= $trace['class'] . $trace['type'] . $trace['function'] . '(' . 
                (count($args)>0? implode(', ', $args) : '') . ')</span></p>';
        }

        echo $str;
        die();
    }


}