<?php

Class Pipe
{
    protected $parent;
    protected $output = null;

    public function __construct($parent)
    {
        $this->parent = $parent;
    }

    public function command($command)
    {
        $this->output = $this->parent->run($command, $this->output)->output();
        return $this->parent;
    }


}