<?php

Class FinalView
{
    public $template;
    public $arguments;

    public function __construct($file, $args)
    {
        $this->template = $file;
        $this->arguments = $args;
    }

    public function __toString()
    {
        return View::loadTemplate($this->template, $this->arguments);
    }

}