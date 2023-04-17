<?php

class HomeController extends Controller
{

    public function showHome()
    {

        global $routes, $base;
        
        $componentName = 'toast-success';
        
        $breadcrumb = array(
            __('login.home') => '#'
        );
        
        return view('index', compact('breadcrumb', 'componentName'));

    }

}
