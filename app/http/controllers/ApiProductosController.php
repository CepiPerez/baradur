<?php

class ApiProductosController extends Controller
{
    public function getUser()
    {
        $user = User::first(); //Auth::user();

        if ($user) {
            $user->avatar = $user->image;
            $user = $user->toArray();
        }

        return response($user, 200);
    }


    public function login(Request $request)
    {
        $user = $request->user;
        $password = $request->password;

        if (!isset($user) || !isset($password))
        {
            header('HTTP/1.1 401 Unauthorized');
            header('Content-Type: application/json');
            echo json_encode(array("error"=>"Access denied. Please provide user credentials"));
            __exit();
        }

        $token = Auth::api_login($user, $password);

        return response(array("token"=>$token), 200);
    }

    public function index(Request $request)
    {
        $data = Producto::with(['categoria', 'precio']);
            
        if ($request->buscar) {
            $data = $data->where('descripcion', 'like' , "%$request->buscar%")
                ->orWhere('codigo', 'like' , "%$request->buscar%");
        }
        
        $data = $data->orderBy('codigo')->get();

        return response($data, 200);
        
    }

    public function show($id)
    {
        $data = Producto::selectRaw('codigo, productos.descripcion,  
           categorias.id as categoria, categorias.descripcion as desc_categoria')
            ->leftJoin('categorias', 'id', '=', 'categoria_id')
            ->where('codigo', $id)->firstOrFail();

        return $data;

        if ($data)
            return response($data, 200);
        else
            return response(array("error"=>"No encontrado"), 400);

    }

    public function store(Request $request)
    {
        
        $result = Producto::create($request->all());
        
        if ($result)
            return response(array("resultado"=>"Agregado correctamente"), '201 Created');
        else
            return response(array("error"=>$result), 400);

    }

    public function update(Request $request, $id)
    {

        $result = Producto::find($id);
        if ($request->codigo) $result->codigo = $request->codigo;
        if ($request->descripcion) $result->descripcion = $request->descripcion;
        if ($request->categoria_id) $result->categoria_id = $request->categoria_id;
        
        $res = $result->save();

        if ($res)
            return response(array("resultado"=>"Modificado correctamente"), 200);
        else
            return response(array("error"=>$res), 400);

    }

    public function destroy($id)
    {
        $result = Producto::where('codigo', $id)->delete();

        if ($result)
            return response(null, 204);
        else
            return response(array("error"=>$result), 400);
        
    }

}

?>