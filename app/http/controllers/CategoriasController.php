<?php

class CategoriasController extends Controller
{

    public function index()
    {

        $title = 'Categorias';
        $data = Categoria::orderBy('id')->paginate(15);

        $breadcrumb = array(
            'Inicio' => '/',
            'Categorias' => '#'
        );

        return view('categorias', compact('title', 'breadcrumb', 'data'));
    }

    public function show(Categoria $categoria)
    {
        $title = $categoria->descripcion;

        $data = $categoria->productos;
        foreach ($data as $dato)
        {
            $dato->valor = number_format(isset($dato->precio->precio)?$dato->precio->precio:0, 2);
        }

        $breadcrumb = array(
            'Inicio' => '/',
            'Categorias' => '#'
        );

        return view('categoria', compact('title', 'breadcrumb', 'categoria', 'data'));
    }

    public function create()
    {
        Gate::authorize('adminCategoria', 'Categoria');
        //$this->authorize('adminCategoria', 'Post');
        
        $title = 'Crear Categoria';

        $breadcrumb = [
            'Inicio' => '/',
            'Categorias' => '/categorias',
            'Crear Categoria' => '#'
        ];

        return view('categorias_crear', compact('title', 'breadcrumb'));
    }

    public function edit(Categoria $categoria)
    {
        //$this->authorize('adminCategoria', $categoria);
        //Gate::forUser(User::first())->authorize('admin-categoria', $categoria);
        

        $title = 'Editar Categoria';

        $categoria->mode = 'edit';

        $breadcrumb = array(
            'Inicio' => '/',
            'Categorias' => '/categorias',
            'Editar Categoria' => '#'
        );

        return view('categorias_crear', compact('title', 'breadcrumb', 'categoria'));
    }

    public function store(Request $request)
    {
        
        $this->authorize('adminCategoria', 'Categoria');

        $request->validate([
            'id' => 'required|unique:categorias',
            'descripcion' => 'required|max:50'
        ]);
        
        $result = Categoria::create($request->validated());
        
        if ($result)
            return to_route('categorias.index')->with('message', 'Se guardaron los datos');
        else {
            return back()->with('error', 'Hubo un error al guardar la categoria');
        }   
    }

    public function update(TestRequest $request, Categoria $categoria)
    {
        $result = $categoria->update($request->validated());

        if ($result)
            return to_route('categorias.index')->with('message', 'Se guardaron los cambios');
        else {
            return back()->with('error', 'Hubo un error al guardar la categoria');
        }
        
    }

    public function destroy(Categoria $categoria)
    {
        /* dd($cat);
        exit(); */

        Gate::authorize('adminCategoria', $categoria);

        $result = $categoria->delete();

        if ($result)
        {
            return back()->with('message', 'Se eliminó la categoría seleccionada');
        }
        else
        {
            return back()->with('error', 'Hubo un error al eliminar la categoría');
        }

    }


}

?>