<?php

Class MessageBag
{
    public $errorBag = array();

    public function __construct()
    {
        if (isset($_SESSION['errors']))
        {
            foreach ($_SESSION['errors'] as $key => $val)
            {
                $this->errorBag[$key] = $val;
            }
        }
        
    }

    public function any()
    {
        return count($this->errorBag)>0;
    } 

    public function all()
    {
        return $this->errorBag;
    }


}