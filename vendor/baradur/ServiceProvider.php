<?php

Class ServiceProvider
{
    protected $observers = array();

    public function __construct()
    {
        global $observers;
        foreach ($this->observers as $model => $class)
        {
            if (!isset($observers[$model]))
                $observers[$model] = $class;
        }
    }



}