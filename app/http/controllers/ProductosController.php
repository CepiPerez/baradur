<?php

enum NewEnumShit
{
    case Asc;
    case Desc;
}


enum ProductCategory:int
{
    case Software = 1;
    case Hardware = 2;
    case Varios = 3;

    public function isSoftware()
    {
        return $this == static::Software;
    }

    public function isHardware()
    {
        return $this == static::Hardware;
    }

    public function isVarios()
    {
        return $this == static::Varios;
    }

    public function getLabelText()
    {
        return match($this) {
            self::Software => 'Categoria: Software',
            self::Hardware => 'Categoria: Hardware',
            self::Varios => 'Categoria: Varios',
        };
    }

    public function getColor()
    {
        return $this->value();
    }

    public static function toArray()
    {
        return [
            [
                'id' => static::Software,
                'name' => 'Software',
                'value' => 1
            ],
            [
                'id' => static::Hardware,
                'name' => 'Hardware',
                'value' => 2
            ]
        ];
    }


}

class ProductosController extends Controller
{
    public function inicio(Request $request)
    {
        //session(['test'=>'hola', 'test2' => 'mundo']);
        //session()->reflash();
        //session()->flash('test3', 'pepe');
        //session()->keep(['test', 'email']);
        //session()->flush();
        //dump(session('test',));
        //dd(session()->pull('test'));
        //dd(session()->all());

        //dd(DB::selectResultSets('CALL getUsersAndCategories()'));
        
        //dd(DB::selectResultSets('select * from users where id=?; select * from categorias where id=?;
        //    select * from productos where categoria_id=?', [Auth::id(), 2, 3]));


        //dump("CUSTOMER", Auth::guard('customer')->attempt(['email' => 'naty@site.com', 'password' => '123456']));
        //Auth::loginUsingId(1);
        //dd(Auth::user());

        //dd(Producto::paginate(5));

        //dd(Prueba::factory()->count(4)->create());

        //dd(DB::delete('delete from pruebas'));
        //Prueba::factory()->count(10)->create();

        /* $res = Producto::where('categoria_id', 3)->first();
        $res->categoria_id = ProductCategory::Hardware;
        dump($res->categoria_id->isHardware()); 
        dump(ProductCategory::tryFrom(1));
        dump(ProductCategory::cases());
        dd($res->categoria_id->isVarios()); */

        /* $res = new Attr;
        $res->descripcion = 'Hola Mundo';
        $res->slug = Str::slug($res->descripcion);
        $res->fecha = now()->subDay();
        dump($res->save()); */

        //Auth::user()->notify(new UserNotification(User::skip(1)->first()));
        //Mail::to('cepiperez@gmail.com')->queue(new UserNotification(User::skip(1)->first()));

        //$prod = Producto::first();
        //ProductCreated::dispatch($prod);
        //event(new ProductCreated($prod));

        /* $proc = Process::timeout(20)->start('./import.sh');
        dump($proc->running());
        while($proc->running()) {
            dump($proc->latestOutput());
            sleep(1);
        }
        dd($proc->wait()); */

        /* $res = 'natalia';
        $result = match($res) {
            'mati' => Producto::first(),
            'naty' => Categoria::first(),
            default => Lottery::odds(1, 3),
        };
        dd($result); */
        
        /* $user = User::skip(1)->first();
        dump(Auth::user()->features()->all());
        dump(Feature::for($user)->all());

        Lottery::fix([true, false, true, false, true]);
        dump(Lottery::odds(1 , 10)->choose());
        dump(Lottery::odds(1 , 10)->choose());
        dump(Lottery::odds(1 , 10)->choose());
        dump(Lottery::odds(1 , 10)->choose());
        dump(Lottery::odds(1 , 10)->choose());
        dump(Lottery::odds(1 , 10)->choose());
        dump(Lottery::odds(1 , 10)->choose());
        dd("FIN"); */
        
        //$this->pepe->show();
        //Producto::selectRaw('count(codigo), descripcion')->get();
        //Producto::select('id')->get();
        //Producto::where('categoria_id', 1)->where('nombre', 'pepe')->dd();

        //return response()->file(public_path('storage/Productos.pdf'));
        //return Blade::render('Hello, {{ $name }}', ['name' => 'Juliana Bashir']);

        //$data = Categoria::with('productos')->paginate(2)->keyBy->slug;
        //dd($data->toArray());

        //return response(Producto::with('categoria')->get());
        //ddd(Producto::paginate(5)->getPaginator());
        //return CategoriaResource::collection(Categoria::paginate(2));
        //return CategoriaResource::make(Categoria::first());
        //return CategoriaResource::collection(Categoria::all());
        //return new CategoriaResource(Categoria::with('productos')->withCount('productos')->skip(2)->first());
        //return new PruebaCollection($data);
        //return new PruebaCollection(Categoria::paginate(2));
        //return PruebaResource::collection($data);
        //return new PruebaResource(Categoria::find(3));
        //return PruebaResource::collection(Categoria::with('productos')->get());
        //return response()->json(['success' => true, 'users' => new PruebaCollection(Producto::where('categoria_id', 3)->get())]);
        //return response()->json(['success' => true, 'data' => new PruebaResource(Producto::find(1001))]);

        //$startTime = microtime(true);
        /* if (Cache::has('productos', request()))
        {
            echo "FROM CACHE<br>";
            $result = Cache::get('productos');
        }
        else 
        {
            echo "FROM DATABASE<br>"; */
            $title = 'Productos';
            
            $buscar = request()->buscar;
            
            $data = Producto::listarProductos(15, $buscar);

            //$data->each->setAttribute('descripcion', $value->descripcion . " !!!");
            
            $breadcrumb = [
                'Inicio' => '/',
                'Productos' => '#'
            ];

            //dd($data);
            
            $result = view('productos', compact('title', 'breadcrumb', 'buscar', 'data')); 
            
            /* Cache::put('productos', $result, 60*60*24);
        } */

        //Cache::flush('productos');
        /* $endTime = microtime(true);
        $time =($endTime-$startTime)*1000;
        echo("Cached in ". round($time, 2) ."ms<br>"); */

        //dd(request()->user());
        return $result;

    }

    public function descargar()
    {
        $title = 'Productos';

        $data = Producto::listarProductos();

        $download = true;

        $view = loadView('productos', compact('title', 'data', 'download'));

        return PDF::inline('Productos', $view);

        //$view = loadView('productos-word', compact('title', 'data'));
        //return Word::download('Productos', $view);
    }

    public function crear()
    {
        Gate::authorize('adminProducto', Producto::class);

        $title = 'Crear Producto';

        $categorias = Categoria::all();

        $breadcrumb = [
            'Inicio' => '/',
            'Productos' => '/productos',
            'Crear Producto' => '#'
        ];

        return view('productos_crear', compact('title', 'breadcrumb', 'categorias'));
    }

    public function editar($producto)
    {
        
        $title = 'Editar Producto';
        
        $producto = Producto::withTrashed()->findOrFail($producto);
        
        Gate::authorize('adminProducto', $producto);

        $producto->mode = 'edit';
        $producto->load('precio');

        $categorias = Categoria::all();

        $breadcrumb = [
            'Inicio' => '/',
            'Productos' => '/productos',
            'Editar Producto' => '#'
        ];

        return view('productos_crear', compact('title', 'breadcrumb', 'producto', 'categorias'));
    }

    public function guardar(Request $request)
    {
        Gate::authorize('adminProducto', 'Producto');
        
        $request->validate([
            'codigo' => 'required|unique:productos',
            'descripcion' => 'required|max:50'
        ]);

        $producto = new Producto;
        $producto->codigo = $request->codigo;
        $producto->descripcion = $request->descripcion;
        $producto->categoria_id = $request->categoria_id;

        $precio = new Precio;
        $precio->precio = $request->precio ? $request->precio : "0";
        $precio->producto_id = $producto->codigo;

        $producto->setRelation('precio', $precio);

        $result = $producto->push();

        if ($result)
        {
            //SmartCache::flush('productos');
            return to_route('productos.listado')->with('message', 'Se creó el producto correctamente');
        }
        else
        {
            return back()->with('error', 'Hubo un error al guardar el producto');
        }
        
    }

    public function modificar(Request $request, Producto $producto)
    {
        Gate::authorize('adminProducto', 'Producto');

        $request->validate([
            'codigo' => 'required|unique:productos,codigo,'.$request->original,
            'descripcion' => 'required|max:50'
        ]);

        //$result = $producto->update($request->all());

        $producto->codigo = $request->codigo;
        $producto->descripcion = $request->descripcion;
        $producto->categoria_id = $request->categoria_id;
        
        if (!$producto->precio) {
            $producto->precio = new Precio;
            $producto->precio->producto_id = $producto->codigo;
        } 
        
        $producto->precio->precio = $request->precio>0 ? $request->precio : "0";

        $result = $producto->push();

        if ($result)
        {
            //SmartCache::flush('productos');
            return back()->with('message', 'Se modificaron los datos');
        }
        else
        {
            return back()->with('error', 'Hubo un error al modificar el producto');
        }

    }

    public function eliminar($producto)
    {
        Gate::authorize('adminProducto', 'Producto');

        $result = Producto::find($producto)->delete();

        if ($result)
        {
            //SmartCache::flush('productos');
            return back()->with('message', 'Se eliminó el producto seleccionado');
        }
        else
        {
            return back()->with('error', 'Hubo un error al eliminar el producto');
        }
    }

}

?>