<?php


class HomeController extends Controller
{

    protected $tokenVerification = false;


    public function showHome()
    {

        global $routes, $base;

        # Here we make a list of routes with assigned name
        # only if they use GET method, and show them in Home
        # If the route's name contains '.' then it asumes
        # it has its own section, otherwise its added to main one.

        $data = array();
        $lista = Route::filter('GET', '*')
                ->whereNotIn('controller', array('Auth'))
                ->whereNotContains('url', '{');

        foreach ($lista as $ruta)
        {
            
            # Skip Auth routes and routes with params (between {})
            if (isset($ruta->name)) //) && Gate::allows('check-route', $ruta->name))
            {

                $seccion = SECCION_BASE;
                $titulo = $ruta->name;

                if (count(explode('.', $ruta->name)) > 1)
                {
                    list($seccion, $titulo) = explode('.', $ruta->name);
                    $seccion = ucfirst(str_replace('_', ' ', $seccion));
                }
                
                $arr = array(
                    'titulo' => ucfirst(str_replace('_', ' ', $titulo)),
                    'url' => $ruta->url
                );
                $data[$seccion][] = $arr;
            }
        }
        
        $breadcrump = array(
            __('login.home') => '#'
        );
        
        return view('index', compact('breadcrump', 'data'));

    }

}
