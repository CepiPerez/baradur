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

        if (empty($this->fragments)) {
            return $view->html;
        }

        $html = '';

        foreach ($this->fragments as $fragment) {
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
        foreach ($fragments as $fragment) {
            $this->fragments[] = $fragment;
        }

        return $this;
    }

    public function __call($method, $parameters)
    {
        if (! Str::startsWith($method, 'with')) {
            throw new BadMethodCallException("Method [$method] does not exist on view.");
        }

        return $this->with(Str::camel(substr($method, 4)), $parameters[0]);
    }

    public function with($key, $value)
    {
        $_SESSION['messages'][$key] = $value;

        return $this;
    }

    public function withErrors($errors)
    {
        foreach ($errors as $key => $val) {
            $_SESSION['errors'][$key] = $val;
        }

        return $this;
    }

    public function exists($view)
    {
        return Blade::__findTemplate($view) !== null;
    }

}