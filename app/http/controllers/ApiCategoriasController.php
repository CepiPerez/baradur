<?php

class ApiCategoriasController extends Controller
{

    public function index()
    {
        $data = Categoria::all();

        return response($data, 200);
        
    }

    public function show($id)
    {

    }

    public function store(Request $request)
    {
        
    }

    public function update(Request $request, $id)
    {

    }

    public function destroy($id)
    {

    }

}

?>