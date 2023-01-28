<?php

Class Mailable
{
    public $_template;

    public function build() {}

    public function with($vars)
    {
        foreach ($vars as $key => $val)
        {
            $this->$key = $val;
        }
        
        return $this;
    }

    public function view($file)
    {
        $this->_template = $file;

        return $this;
    }
    
}