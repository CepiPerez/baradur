<?php

class HomeController extends Controller
{

    public function index()
    {

        $breadcrumb = array(
            __('login.home') => '#'
        );
        
        return view('index', compact('breadcrumb'));

    }

}
