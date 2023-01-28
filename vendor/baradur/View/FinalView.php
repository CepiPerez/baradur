<?php

Class FinalView
{
    public $template;
    public $arguments;
    public $fragments = array();

    public function __construct($file, $args)
    {
        $this->template = $file;
        $this->arguments = $args;
    }

    public function __toString()
    {
        $view = View::renderTemplate($this->template, $this->arguments, true);

        if (empty($this->fragments))
        {
            return $view->html;
        }

        $html = '';
        foreach ($this->fragments as $fragment)
        {
            $html .= $view->getFragment($fragment);
        }

        return $html;
    }

    public function fragment($fragment)
    {
       return $this->fragments(array($fragment));
    }

    public function fragments($fragments)
    {
        foreach ($fragments as $fragment)
        {
            $this->fragments[] = $fragment;
        }

        return $this;
    }

}