<?php

class Testing
{

    public function __invoke()
    {
        /* $this->pepe->show();

        Producto::selectRaw('count(descripcion)')
            ->leftJoin('categorias', 'id', 'codigo')
            ->get(); */
        
        $this->showStart();

        $q = Categoria::skip(1)->first()->toArray();
        //dump(json_encode($q));
        $res = '{"id":"2","descripcion":"Hardware"}';
        $this->showResult("Testing skip()... ", json_encode($q), $res);

        $q = Producto::find([1001, 1002])->toArray();
        //dd($q);
        $res = '[{"codigo":"1001","descripcion":"Windows 10 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 18:05:57","test":"pepe"},{"codigo":"1002","descripcion":"Windows 10 Server","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2022-12-03 11:43:54","test":"pepe"}]';
        $this->showResult("Testing find() with 2 items... ", json_encode($q), $res);

        $q = PlaylistContent::with('tags')->get()->toArray();
        //$res = '[{"id":1,"playlist_id":1,"artist":"Ramones","album":"Animal boy","song":"My brain is hanging upside down","tags":[{"id":5,"descripcion":"internacional","pivot":{"taggable_id":1,"tag_id":5}},{"id":8,"descripcion":"punk rock","pivot":{"taggable_id":1,"tag_id":8}}]},{"id":2,"playlist_id":1,"artist":"Ramones","album":"Mondo bizarro","song":"Heidy is a headcase","tags":[{"id":5,"descripcion":"internacional","pivot":{"taggable_id":2,"tag_id":5}},{"id":8,"descripcion":"punk rock","pivot":{"taggable_id":2,"tag_id":8}}]},{"id":3,"playlist_id":1,"artist":"Ramones","album":"Road to ruin","song":"Sheena is a punk rocker","tags":[{"id":5,"descripcion":"internacional","pivot":{"taggable_id":3,"tag_id":5}},{"id":8,"descripcion":"punk rock","pivot":{"taggable_id":3,"tag_id":8}}]},{"id":4,"playlist_id":1,"artist":"Attaque 77","album":"Todo esta al reves","song":"Cuarto poder","tags":[{"id":6,"descripcion":"nacional","pivot":{"taggable_id":4,"tag_id":6}},{"id":8,"descripcion":"punk rock","pivot":{"taggable_id":4,"tag_id":8}}]},{"id":5,"playlist_id":1,"artist":"Attaque 77","album":"Todo esta al reves","song":"Flores robadas","tags":[{"id":6,"descripcion":"nacional","pivot":{"taggable_id":5,"tag_id":6}},{"id":8,"descripcion":"punk rock","pivot":{"taggable_id":5,"tag_id":8}}]},{"id":6,"playlist_id":2,"artist":"Radiohead","album":"Ok computer","song":"Paranoid android","tags":[]},{"id":7,"playlist_id":2,"artist":"Radiohead","album":"Ok computer","song":"Lucky","tags":[]},{"id":8,"playlist_id":2,"artist":"Nirvana","album":"Nevermind","song":"Smells like teen spirit","tags":[]},{"id":9,"playlist_id":3,"artist":"Luis Miguel","album":"Romance","song":"Nada es igual","tags":[]},{"id":10,"playlist_id":3,"artist":"Luis Miguel","album":"Aries","song":"Cuando calienta el sol","tags":[]},{"id":11,"playlist_id":3,"artist":"Alejandro Fernandez","album":"Me estoy enamorando","song":"Me estoy enamorando","tags":[]},{"id":12,"playlist_id":4,"artist":"La Los Fabulosos Cadillacs","album":"Volumen 5","song":"El leon","tags":[]},{"id":13,"playlist_id":5,"artist":"Ramones","album":"Road to ruin","song":"Needles and pins","tags":[{"id":5,"descripcion":"internacional","pivot":{"taggable_id":13,"tag_id":5}},{"id":8,"descripcion":"punk rock","pivot":{"taggable_id":13,"tag_id":8}}]}]';
        $res = '[{"id":"1","playlist_id":"1","artist":"Ramones","album":"Animal boy","song":"My brain is hanging upside down","tags":[{"id":"5","descripcion":"internacional","pivot":{"taggable_id":"1","tag_id":"5"}},{"id":"8","descripcion":"punk rock","pivot":{"taggable_id":"1","tag_id":"8"}}]},{"id":"2","playlist_id":"1","artist":"Ramones","album":"Mondo bizarro","song":"Heidy is a headcase","tags":[{"id":"5","descripcion":"internacional","pivot":{"taggable_id":"2","tag_id":"5"}},{"id":"8","descripcion":"punk rock","pivot":{"taggable_id":"2","tag_id":"8"}}]},{"id":"3","playlist_id":"1","artist":"Ramones","album":"Road to ruin","song":"Sheena is a punk rocker","tags":[{"id":"5","descripcion":"internacional","pivot":{"taggable_id":"3","tag_id":"5"}},{"id":"8","descripcion":"punk rock","pivot":{"taggable_id":"3","tag_id":"8"}}]},{"id":"4","playlist_id":"1","artist":"Attaque 77","album":"Todo esta al reves","song":"Cuarto poder","tags":[{"id":"6","descripcion":"nacional","pivot":{"taggable_id":"4","tag_id":"6"}},{"id":"8","descripcion":"punk rock","pivot":{"taggable_id":"4","tag_id":"8"}}]},{"id":"5","playlist_id":"1","artist":"Attaque 77","album":"Todo esta al reves","song":"Flores robadas","tags":[{"id":"6","descripcion":"nacional","pivot":{"taggable_id":"5","tag_id":"6"}},{"id":"8","descripcion":"punk rock","pivot":{"taggable_id":"5","tag_id":"8"}}]},{"id":"6","playlist_id":"2","artist":"Radiohead","album":"Ok computer","song":"Paranoid android","tags":[]},{"id":"7","playlist_id":"2","artist":"Radiohead","album":"Ok computer","song":"Lucky","tags":[]},{"id":"8","playlist_id":"2","artist":"Nirvana","album":"Nevermind","song":"Smells like teen spirit","tags":[]},{"id":"9","playlist_id":"3","artist":"Luis Miguel","album":"Romance","song":"Nada es igual","tags":[]},{"id":"10","playlist_id":"3","artist":"Luis Miguel","album":"Aries","song":"Cuando calienta el sol","tags":[]},{"id":"11","playlist_id":"3","artist":"Alejandro Fernandez","album":"Me estoy enamorando","song":"Me estoy enamorando","tags":[]},{"id":"12","playlist_id":"4","artist":"La Los Fabulosos Cadillacs","album":"Volumen 5","song":"El leon","tags":[]},{"id":"13","playlist_id":"5","artist":"Ramones","album":"Road to ruin","song":"Needles and pins","tags":[{"id":"5","descripcion":"internacional","pivot":{"taggable_id":"13","tag_id":"5"}},{"id":"8","descripcion":"punk rock","pivot":{"taggable_id":"13","tag_id":"8"}}]}]';
        $this->showResult("Testing MorphMany()... ", json_encode($q), $res);

        $band = 'Ramones';
        $q = Musica::withWhereHas('playlists.songs', fn($q) => $q->where('artist', $band))->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456","playlists":[{"pid":"1","user_id":"1","name":"Rock","songs":[{"id":"1","playlist_id":"1","artist":"Ramones","album":"Animal boy","song":"My brain is hanging upside down"},{"id":"2","playlist_id":"1","artist":"Ramones","album":"Mondo bizarro","song":"Heidy is a headcase"},{"id":"3","playlist_id":"1","artist":"Ramones","album":"Road to ruin","song":"Sheena is a punk rocker"}]}]},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456","playlists":[{"pid":"5","user_id":"4","name":"Mi Musica","songs":[{"id":"13","playlist_id":"5","artist":"Ramones","album":"Road to ruin","song":"Needles and pins"}]}]}]';
        $this->showResult("Testing withWhereHas() with arrow function... ", json_encode($q), $res);

        $q = Musica::withWhereHas('playlists.songs', function ($query) use ($band) {
            $query->where('artist', $band);
        })->get()->toArray();
        $this->showResult("Testing withWhereHas() with annonymous function... ", json_encode($q), $res);

        $q = Producto::orderBy(Categoria::select('descripcion')->whereColumn('id', 'categoria_id'))->orderBy('codigo')->get()->toArray();
        $res = '[{"codigo":"2000","descripcion":"Motherboard Msi","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2001","descripcion":"Motherboard Intel","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2100","descripcion":"Placa Xfx Geforce 1050","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2101","descripcion":"Placa Xfx Geforce 1080","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2102","descripcion":"Placa Xfx Geforce 1080 Ti","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2500","descripcion":"Mouse Generico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2501","descripcion":"Mouse Inalambrico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2502","descripcion":"Teclado Generico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2503","descripcion":"Teclado Inalambrico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2600","descripcion":"Gabinete Generico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2601","descripcion":"Gabinete Watercooling","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1000","descripcion":"Windows 10 Home","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 14:57:17","test":"pepe"},{"codigo":"1001","descripcion":"Windows 10 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 18:05:57","test":"pepe"},{"codigo":"1002","descripcion":"Windows 10 Server","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2022-12-03 11:43:54","test":"pepe"},{"codigo":"1010","descripcion":"Windows 11 Home","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":null,"test":"pepe"},{"codigo":"1011","descripcion":"Windows 11 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":null,"test":"pepe"},{"codigo":"1100","descripcion":"Norton Antivirus","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1101","descripcion":"Avast Antivirus","active":true,"categoria_id":1,"created_at":"2022-10-15","updated_at":null,"test":"pepe"},{"codigo":"1102","descripcion":"Symantec Antivirus","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1200","descripcion":"Adobe Photoshop","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1201","descripcion":"Adobe Aftereffects","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1202","descripcion":"Adobe Illustrator","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1300","descripcion":"Autodesk Autocad","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2700","descripcion":"Cable Sata","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2701","descripcion":"Cable Usb","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2702","descripcion":"Cable Impresora Usb","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2703","descripcion":"Cable Impresora Lpt","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":"2022-12-06 21:31:39","test":"pepe"},{"codigo":"2704","descripcion":"Cable Usb Tipo C","active":true,"categoria_id":3,"created_at":"2022-11-22","updated_at":null,"test":"pepe"}]';
        
        $q = Producto::with('image')->paginate(5)->toArray();
        $res = '[{"codigo":"1000","descripcion":"Windows 10 Home","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 14:57:17","test":"pepe","image":null},{"codigo":"1001","descripcion":"Windows 10 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 18:05:57","test":"pepe","image":{"url":"imagen1.jpg","imageable_id":"1001","imageable_type":"Producto"}},{"codigo":"1002","descripcion":"Windows 10 Server","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2022-12-03 11:43:54","test":"pepe","image":null},{"codigo":"1010","descripcion":"Windows 11 Home","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":null,"test":"pepe","image":null},{"codigo":"1011","descripcion":"Windows 11 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":null,"test":"pepe","image":null}]';
        $this->showResult("Testing morphOne()... ", json_encode($q), $res);
        
        $q = Categoria::skip(1)->get()->load('productos')->toArray();
        $res = '[{"id":"2","descripcion":"Hardware","productos":[{"codigo":"2000","descripcion":"Motherboard Msi","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2001","descripcion":"Motherboard Intel","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2100","descripcion":"Placa Xfx Geforce 1050","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2101","descripcion":"Placa Xfx Geforce 1080","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2102","descripcion":"Placa Xfx Geforce 1080 Ti","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2500","descripcion":"Mouse Generico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2501","descripcion":"Mouse Inalambrico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2502","descripcion":"Teclado Generico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2503","descripcion":"Teclado Inalambrico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2600","descripcion":"Gabinete Generico","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2601","descripcion":"Gabinete Watercooling","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"}]},{"id":"3","descripcion":"Varios","productos":[{"codigo":"2700","descripcion":"Cable Sata","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2701","descripcion":"Cable Usb","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2702","descripcion":"Cable Impresora Usb","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2703","descripcion":"Cable Impresora Lpt","active":true,"categoria_id":3,"created_at":"2022-10-12","updated_at":"2022-12-06 21:31:39","test":"pepe"},{"codigo":"2704","descripcion":"Cable Usb Tipo C","active":true,"categoria_id":3,"created_at":"2022-11-22","updated_at":null,"test":"pepe"}]}]';
        $this->showResult("Testing Collection load()... ", json_encode($q), $res);
        
        $q = Categoria::first()->load('productos')->toArray();
        $res = '{"id":"1","descripcion":"Software","productos":[{"codigo":"1000","descripcion":"Windows 10 Home","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 14:57:17","test":"pepe"},{"codigo":"1001","descripcion":"Windows 10 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 18:05:57","test":"pepe"},{"codigo":"1002","descripcion":"Windows 10 Server","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2022-12-03 11:43:54","test":"pepe"},{"codigo":"1010","descripcion":"Windows 11 Home","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":null,"test":"pepe"},{"codigo":"1011","descripcion":"Windows 11 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":null,"test":"pepe"},{"codigo":"1100","descripcion":"Norton Antivirus","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1101","descripcion":"Avast Antivirus","active":true,"categoria_id":1,"created_at":"2022-10-15","updated_at":null,"test":"pepe"},{"codigo":"1102","descripcion":"Symantec Antivirus","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1200","descripcion":"Adobe Photoshop","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1201","descripcion":"Adobe Aftereffects","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1202","descripcion":"Adobe Illustrator","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"1300","descripcion":"Autodesk Autocad","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"}]}';
        $this->showResult("Testing Model load()... ", json_encode($q), $res);

        $q = Musica::with('songs')->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456","songs":[{"id":"1","playlist_id":"1","artist":"Ramones","album":"Animal boy","song":"My brain is hanging upside down","bardur_through_key":"1"},{"id":"2","playlist_id":"1","artist":"Ramones","album":"Mondo bizarro","song":"Heidy is a headcase","bardur_through_key":"1"},{"id":"3","playlist_id":"1","artist":"Ramones","album":"Road to ruin","song":"Sheena is a punk rocker","bardur_through_key":"1"},{"id":"4","playlist_id":"1","artist":"Attaque 77","album":"Todo esta al reves","song":"Cuarto poder","bardur_through_key":"1"},{"id":"5","playlist_id":"1","artist":"Attaque 77","album":"Todo esta al reves","song":"Flores robadas","bardur_through_key":"1"},{"id":"6","playlist_id":"2","artist":"Radiohead","album":"Ok computer","song":"Paranoid android","bardur_through_key":"1"},{"id":"7","playlist_id":"2","artist":"Radiohead","album":"Ok computer","song":"Lucky","bardur_through_key":"1"},{"id":"8","playlist_id":"2","artist":"Nirvana","album":"Nevermind","song":"Smells like teen spirit","bardur_through_key":"1"}]},{"id":"2","name":"Agustina","email":"agus.perez@hotmail.com","password":"123456","songs":[]},{"id":"3","name":"Natalia","email":"naty-barba@hotmail.com","password":"123456","songs":[{"id":"9","playlist_id":"3","artist":"Luis Miguel","album":"Romance","song":"Nada es igual","bardur_through_key":"3"},{"id":"10","playlist_id":"3","artist":"Luis Miguel","album":"Aries","song":"Cuando calienta el sol","bardur_through_key":"3"},{"id":"11","playlist_id":"3","artist":"Alejandro Fernandez","album":"Me estoy enamorando","song":"Me estoy enamorando","bardur_through_key":"3"},{"id":"12","playlist_id":"4","artist":"La Los Fabulosos Cadillacs","album":"Volumen 5","song":"El leon","bardur_through_key":"3"}]},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456","songs":[{"id":"13","playlist_id":"5","artist":"Ramones","album":"Road to ruin","song":"Needles and pins","bardur_through_key":"4"}]}]';
        $this->showResult("Testing hasManyThrough()... ", json_encode($q), $res);
        
        $q = Grupo::with('miembros')->get()->toArray();
        $res = '[{"id":"1","descripcion":"Papis","miembros":[{"id":"1","username":"cepi","email":"cepiperez@gmail.com","name":"Matias Perez","relacion":{"grupo_id":"1","user_id":"1","active":"activo"}},{"id":"2","username":"naty","email":"naty-barba@hotmail.com","name":"Natalia","relacion":{"grupo_id":"1","user_id":"2","active":"inactivo"}}]},{"id":"2","descripcion":"Nenas","miembros":[]}]';
        $this->showResult("Testing belongsToMany()... ", json_encode($q), $res);
        
        $q = Tag::with('playlists')->get()->toArray();
        $res = '[{"id":"1","descripcion":"rock","playlists":[{"pid":"1","user_id":"1","name":"Rock","pivot":{"taggable_id":"1","tag_id":"1"}},{"pid":"5","user_id":"4","name":"Mi Musica","pivot":{"taggable_id":"5","tag_id":"1"}}]},{"id":"2","descripcion":"pop","playlists":[]},{"id":"3","descripcion":"metal","playlists":[]},{"id":"4","descripcion":"latino","playlists":[{"pid":"3","user_id":"3","name":"Lentos","pivot":{"taggable_id":"3","tag_id":"4"}}]},{"id":"5","descripcion":"internacional","playlists":[]},{"id":"6","descripcion":"nacional","playlists":[{"pid":"1","user_id":"1","name":"Rock","pivot":{"taggable_id":"1","tag_id":"6"}}]},{"id":"7","descripcion":"alternativo","playlists":[]},{"id":"8","descripcion":"punk rock","playlists":[]}]';
        $this->showResult("Testing morphedByMany()... ", json_encode($q), $res);

        $q = Producto::join('categorias', function ($join) {
            $join->on('categorias.id', '=', 'productos.categoria_id')
                 ->where('categorias.id', '>', 1);
        })->where('codigo', '<', 2200)->get()->toArray();
        $res = '[{"codigo":"2000","descripcion":"Motherboard Msi","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2001","descripcion":"Motherboard Intel","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2100","descripcion":"Placa Xfx Geforce 1050","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2101","descripcion":"Placa Xfx Geforce 1080","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2102","descripcion":"Placa Xfx Geforce 1080 Ti","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"}]';
        $this->showResult("Testing join() with annonymous function... ", json_encode($q), $res);

        $q = Musica::with([
            'playlists' => fn ($q) => $q->where('name', '=', 'Rock')
        ])->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456","playlists":[{"pid":"1","user_id":"1","name":"Rock"}]},{"id":"2","name":"Agustina","email":"agus.perez@hotmail.com","password":"123456","playlists":[]},{"id":"3","name":"Natalia","email":"naty-barba@hotmail.com","password":"123456","playlists":[]},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456","playlists":[]}]';
        $this->showResult("Testing with() with arrow function... ", json_encode($q), $res);

        $q = Musica::has('playlists', '=', 2)->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456"},{"id":"3","name":"Natalia","email":"naty-barba@hotmail.com","password":"123456"}]';
        $this->showResult("Testing has()... ", json_encode($q), $res);

        $cb = fn ($query) => $query->where('artist', 'Nirvana');
        $q = Musica::with([
            'playlists' => fn($q) => $q->where('name', '!=', 'Rock')->with(['songs' => $cb])
        ])->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456","playlists":[{"pid":"2","user_id":"1","name":"Varios","songs":[{"id":"8","playlist_id":"2","artist":"Nirvana","album":"Nevermind","song":"Smells like teen spirit"}]}]},{"id":"2","name":"Agustina","email":"agus.perez@hotmail.com","password":"123456","playlists":[{"pid":"6","user_id":"2","name":"Mis canciones","songs":[]}]},{"id":"3","name":"Natalia","email":"naty-barba@hotmail.com","password":"123456","playlists":[{"pid":"3","user_id":"3","name":"Lentos","songs":[]},{"pid":"4","user_id":"3","name":"Clasicos","songs":[]}]},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456","playlists":[{"pid":"5","user_id":"4","name":"Mi Musica","songs":[]}]}]';
        $this->showResult("Testing with() with relation and arrow function... ", json_encode($q), $res);

        $cb = function ($query) {
            $query->where('artist', 'Nirvana');
        };
        $q = Musica::with([
            'playlists' => function ($query) use($cb) {
                $query->where('name', '!=', 'Rock')->with(['songs' => $cb]);
            }
        ])->get()->toArray();
        $this->showResult("Testing with() with relation and annonymous function... ", json_encode($q), $res);


        $q = Musica::whereHas('playlists', function($query) {
            $query->whereHas('songs', function($sub) {
                $sub->where('artist', 'Ramones');
            });
                
        })->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456"},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456"}]';
        $this->showResult("Testing whereHas() with nested function... ", json_encode($q), $res);

        $q = Musica::whereHas('playlists.songs', function(Builder $query) {
                $query->where('artist', 'Ramones');
        })->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456"},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456"}]';
        $this->showResult("Testing whereHas() with nested relationship... ", json_encode($q), $res);



        $q = Musica::withCount([
            'songs as ramones' => function ($query) {
                $query->where('artist', 'Ramones');
            },
            'songs as luisito' => function ($query) {
                $query->where('artist', 'Luis Miguel');
            }

        ])->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456","ramones":"3","luisito":"0"},{"id":"2","name":"Agustina","email":"agus.perez@hotmail.com","password":"123456","ramones":"0","luisito":"0"},{"id":"3","name":"Natalia","email":"naty-barba@hotmail.com","password":"123456","ramones":"0","luisito":"2"},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456","ramones":"1","luisito":"0"}]';
        $this->showResult("Testing withCount() with annonymous function... ", json_encode($q), $res);

        $q = Musica::withCount([
            'songs as ramones' => fn($query) => $query->where('artist', 'Ramones'),
            'songs as luisito' => fn($query) => $query->where('artist', 'Luis Miguel')
        ])->get()->toArray();
        $this->showResult("Testing withCount() with arrow function... ", json_encode($q), $res);

        $q = Producto::whereRelation('precio', 'precio', '>=', 60)->with('precio')->get()->toArray();
        $res = '[{"codigo":"1200","descripcion":"Adobe Photoshop","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe","precio":{"id":"8","producto_id":"1200","precio":"98.00"}},{"codigo":"2000","descripcion":"Motherboard Msi","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe","precio":{"id":"14","producto_id":"2000","precio":"160.00"}},{"codigo":"2001","descripcion":"Motherboard Intel","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe","precio":{"id":"15","producto_id":"2001","precio":"180.00"}},{"codigo":"2100","descripcion":"Placa Xfx Geforce 1050","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe","precio":{"id":"16","producto_id":"2100","precio":"680.00"}},{"codigo":"2101","descripcion":"Placa Xfx Geforce 1080","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe","precio":{"id":"17","producto_id":"2101","precio":"1,050.00"}},{"codigo":"2102","descripcion":"Placa Xfx Geforce 1080 Ti","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe","precio":{"id":"18","producto_id":"2102","precio":"1,250.00"}},{"codigo":"2601","descripcion":"Gabinete Watercooling","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe","precio":{"id":"24","producto_id":"2601","precio":"180.00"}}]';
        $this->showResult("Testing whereRelation()... ", json_encode($q), $res);

        $q = Producto::orderBy('codigo')->get()->pluck('descripcion', 'codigo')->toArray();
        $res = '{"1000":"Windows 10 Home","1001":"Windows 10 Professional","1002":"Windows 10 Server","1010":"Windows 11 Home","1011":"Windows 11 Professional","1100":"Norton Antivirus","1101":"Avast Antivirus","1102":"Symantec Antivirus","1200":"Adobe Photoshop","1201":"Adobe Aftereffects","1202":"Adobe Illustrator","1300":"Autodesk Autocad","2000":"Motherboard Msi","2001":"Motherboard Intel","2100":"Placa Xfx Geforce 1050","2101":"Placa Xfx Geforce 1080","2102":"Placa Xfx Geforce 1080 Ti","2500":"Mouse Generico","2501":"Mouse Inalambrico","2502":"Teclado Generico","2503":"Teclado Inalambrico","2600":"Gabinete Generico","2601":"Gabinete Watercooling","2700":"Cable Sata","2701":"Cable Usb","2702":"Cable Impresora Usb","2703":"Cable Impresora Lpt","2704":"Cable Usb Tipo C"}';
        $this->showResult("Testing Collection pluck()... ", json_encode($q), $res);

        $q = Producto::with('categoria')->get()->map(
            function ($value) {
                return ['id' => $value->codigo,
                    'name' => Str::upper($value->descripcion),
                    'rand' => $value->categoria->descripcion
                ];
            }
        )->toArray();
        $res = '[{"id":"1000","name":"WINDOWS 10 HOME","rand":"Software"},{"id":"1001","name":"WINDOWS 10 PROFESSIONAL","rand":"Software"},{"id":"1002","name":"WINDOWS 10 SERVER","rand":"Software"},{"id":"1010","name":"WINDOWS 11 HOME","rand":"Software"},{"id":"1011","name":"WINDOWS 11 PROFESSIONAL","rand":"Software"},{"id":"1100","name":"NORTON ANTIVIRUS","rand":"Software"},{"id":"1101","name":"AVAST ANTIVIRUS","rand":"Software"},{"id":"1102","name":"SYMANTEC ANTIVIRUS","rand":"Software"},{"id":"1200","name":"ADOBE PHOTOSHOP","rand":"Software"},{"id":"1201","name":"ADOBE AFTEREFFECTS","rand":"Software"},{"id":"1202","name":"ADOBE ILLUSTRATOR","rand":"Software"},{"id":"1300","name":"AUTODESK AUTOCAD","rand":"Software"},{"id":"2000","name":"MOTHERBOARD MSI","rand":"Hardware"},{"id":"2001","name":"MOTHERBOARD INTEL","rand":"Hardware"},{"id":"2100","name":"PLACA XFX GEFORCE 1050","rand":"Hardware"},{"id":"2101","name":"PLACA XFX GEFORCE 1080","rand":"Hardware"},{"id":"2102","name":"PLACA XFX GEFORCE 1080 TI","rand":"Hardware"},{"id":"2500","name":"MOUSE GENERICO","rand":"Hardware"},{"id":"2501","name":"MOUSE INALAMBRICO","rand":"Hardware"},{"id":"2502","name":"TECLADO GENERICO","rand":"Hardware"},{"id":"2503","name":"TECLADO INALAMBRICO","rand":"Hardware"},{"id":"2600","name":"GABINETE GENERICO","rand":"Hardware"},{"id":"2601","name":"GABINETE WATERCOOLING","rand":"Hardware"},{"id":"2700","name":"CABLE SATA","rand":"Varios"},{"id":"2701","name":"CABLE USB","rand":"Varios"},{"id":"2702","name":"CABLE IMPRESORA USB","rand":"Varios"},{"id":"2703","name":"CABLE IMPRESORA LPT","rand":"Varios"},{"id":"2704","name":"CABLE USB TIPO C","rand":"Varios"}]';
        $this->showResult("Testing Collection map()... ", json_encode($q), $res);

        $q = Producto::whereStatus(1)->whereDescriptionOrCategoriaId('windows', 1)->toPlainSql();
        $res = "SELECT `productos`.* FROM `productos` WHERE `productos`.`status` = 1 AND (`productos`.`description` = 'windows' OR `productos`.`categoria_id` = 1)";
        $this->showResult("Testing where in Buider::__call()... ", $q, $res);

        $q = Musica::whereWith('playlists.songs', function($query) {
            $query->where('artist', 'Ramones');
        })->get()->toArray();
        $res = '[{"id":"1","name":"Matias Perez","email":"cepiperez@gmail.com","password":"123456","playlists":[{"pid":"1","user_id":"1","name":"Rock","songs":[{"id":"1","playlist_id":"1","artist":"Ramones","album":"Animal boy","song":"My brain is hanging upside down"},{"id":"2","playlist_id":"1","artist":"Ramones","album":"Mondo bizarro","song":"Heidy is a headcase"},{"id":"3","playlist_id":"1","artist":"Ramones","album":"Road to ruin","song":"Sheena is a punk rocker"}]},{"pid":"2","user_id":"1","name":"Varios","songs":[]}]},{"id":"4","name":"Micaela","email":"mmicaela@hotmail.com","password":"123456","playlists":[{"pid":"5","user_id":"4","name":"Mi Musica","songs":[{"id":"13","playlist_id":"5","artist":"Ramones","album":"Road to ruin","song":"Needles and pins"}]}]}]';
        $this->showResult("Testing scope... ", json_encode($q), $res);

        $q = Producto::whereNot(function($query) {
            $query->where('codigo', 1);
            $query->orWhere(function($query) {
                $query->where('descripcion', 'windows');
                $query->whereIn('status', [100, 101]);
            });
        })->toPlainSql();
        $res = "SELECT `productos`.* FROM `productos` WHERE NOT (`productos`.`codigo` = 1 OR (`productos`.`descripcion` = 'windows' AND `productos`.`status` IN (100,101)))";
        $this->showResult("Testing where() with nested annonymous functions... ", $q, $res);

        $q = Producto::whereNot([
            ['codigo' => 1], ['descripcion' => 'windows']
        ])->toPlainSql();
        $res = "SELECT `productos`.* FROM `productos` WHERE NOT (`productos`.`codigo` = 1 AND `productos`.`descripcion` = 'windows')";
        $this->showResult("Testing where() with arrays... ", $q, $res);

        $q = Prueba::factory()->count(5)->make();
        $this->showResult("Testing Factory make()... ", $q->count(), 5);
        
        $q = Producto::when(true, function($q) {
            $q->where('pepe', 1);
        })->toPlainSql();
        $res = "SELECT `productos`.* FROM `productos` WHERE `productos`.`pepe` = 1";
        $this->showResult("Testing when()... ", $q, $res);

        $q = Categoria::addSelect(['ultimo_producto' => Producto::select('descripcion')
            ->whereColumn('categoria_id', 'categorias.id')
            ->orderByDesc('codigo')
            ->limit(1)
        ])->get()->toArray();
        $res = '[{"id":"1","descripcion":"Software","ultimo_producto":"autodesk autocad"},{"id":"2","descripcion":"Hardware","ultimo_producto":"gabinete watercooling"},{"id":"3","descripcion":"Varios","ultimo_producto":"cable usb tipo c"}]';
        $this->showResult("Testing addSelect() with subquery... ", json_encode($q), $res);

        $cb = DB::table('posts')
            ->selectRaw('user_id, MAX(created_at) as last_post_created_at')
            ->where('is_published', true)
            ->groupBy('user_id');
        $q = DB::table('users')
            ->joinSub($cb, 'latest_posts', function ($join) {
                $join->on('users.id', '=', 'latest_posts.user_id');
            })->toPlainSql();
        $res = "SELECT `users`.* FROM `users` INNER JOIN (SELECT user_id, MAX(created_at) as last_post_created_at FROM `posts` WHERE `posts`.`is_published` = 1 GROUP BY `posts`.`user_id`) as `latest_posts` ON `users`.`id` = `latest_posts`.`user_id`";
        $this->showResult("Testing joinSub()... ", htmlentities($q), htmlentities($res));

        $res = Musica::first();
        $q = $res->playlists->load('tags')->toArray();
        $res = '[{"pid":"1","user_id":"1","name":"Rock","tags":[{"id":"6","descripcion":"nacional","pivot":{"taggable_id":"1","tag_id":"6"}},{"id":"1","descripcion":"rock","pivot":{"taggable_id":"1","tag_id":"1"}}]},{"pid":"2","user_id":"1","name":"Varios","tags":[]}]';
        $this->showResult("Testing lazy loading with nested load... ", json_encode($q), $res);


        Test::truncate();
        $q =  Test::firstOrCreate(
            ['name' => 'pedro'],
            ['descripcion' => 'pedro puto', 'email' => 'pedro@gmail.com']
        );
        //dump($q, true); die();
        $res = '{"id":"1","name":"pedro","descripcion":"pedro puto","email":"pedro@gmail.com"}';
        $this->showResult("Testing firstOrCreate() on new... ", json_encode($q->toArray()), $res);
        $this->showResult("Testing wasRecentlyCreated on new... ", $q->wasRecentlyCreated, true);
        
        $q =  Test::firstOrCreate(
            ['name' => 'pedro'],
            ['descripcion' => 'should be ignored', 'email' => 'should be ignored too']
        );
        $res = '{"id":"1","name":"pedro","descripcion":"pedro puto","email":"pedro@gmail.com"}';
        $this->showResult("Testing firstOrCreate() on existent... ", json_encode($q->toArray()), $res);
        $this->showResult("Testing wasRecentlyCreated on existent... ", $q->wasRecentlyCreated, false);

        $q = Test::firstOrNew(
            ['name' => 'pablo'],
            ['descripcion' => 'pablo puto', 'email' => 'pablo@gmail.com']
        )->toArray();
        $res = '{"name":"pablo","descripcion":"pablo puto","email":"pablo@gmail.com"}';
        $this->showResult("Testing firstOrNew()... ", json_encode($q), $res);

        $q = Test::firstOrNew(
            ['name' => 'pedro'],
            ['descripcion' => 'pablo puto', 'email' => 'pablo@gmail.com']
        )->toArray();
        $res = '{"id":"1","name":"pedro","descripcion":"pedro puto","email":"pedro@gmail.com"}';
        $this->showResult("Testing firstOrNew() on existent... ", json_encode($q), $res);

        $q = Test::updateOrCreate(
            ['name' => 'juan'],
            ['descripcion' => 'Juancito puto', 'email' => 'juan@gmail.com']
        );
        $res = '{"id":"2","name":"juan","descripcion":"Juancito puto","email":"juan@gmail.com"}';
        $this->showResult("Testing updateOrCreate()... ", json_encode($q->toArray()), $res);

        $this->showResult("Testing wasRecentlyCreated on new", $q->wasRecentlyCreated, true);

        $q = Test::updateOrCreate(
            ['name' => 'juan'],
            ['descripcion' => 'Juancito puto2', 'email' => 'juan@gmail.com']
        );
        $res = '{"id":"2","name":"juan","descripcion":"Juancito puto2","email":"juan@gmail.com"}';
        $this->showResult("Testing updateOrCreate() on existent... ", json_encode($q->toArray()), $res);

        $this->showResult("Testing wasRecentlyCreated on existent", $q->wasRecentlyCreated, false);

        $q = Test::insertOrIgnore(
            ['name' => 'lola', 'descripcion' => 'lolita hermosa', 'email' => 'lolita@gmail.com']
        );
        $this->showResult("Testing insertOrIgnore()... ", $q, true);
        $q = Test::insertOrIgnore(
            ['name' => 'lola', 'descripcion' => 'lolita hermosa2', 'email' => 'lolita@gmail.com']
        );
        $q = Test::where('name', 'lola')->first()->toArray();
        $res = '{"id":"3","name":"lola","descripcion":"lolita hermosa","email":"lolita@gmail.com"}';
        $this->showResult("Testing insertOrIgnore() on existent... ", json_encode($q), $res);

        $q = Test::upsert([
            ['id' => 1, 'name' => 'pedro', 'descripcion' => 'pedro capo', 'email' => 'pedro@gmail.com'],
            ['id' => 5, 'name' => 'juana', 'descripcion' => 'juanita loca', 'email' => 'juanita@gmail.com']
        ], ['name'], ['email', 'descripcion']);
        $q = Test::where('name', 'pedro')->orWhere('name', 'juana')->get()->toArray();
        $res = '[{"id":"1","name":"pedro","descripcion":"pedro capo","email":"pedro@gmail.com"},{"id":"5","name":"juana","descripcion":"juanita loca","email":"juanita@gmail.com"}]';
        $this->showResult("Testing upsert()... ", json_encode($q), $res);

        $q = app(TestClass::class)->increase();
        $q .= app('test1')->increase();
        $q .= app('test1')->increase();
        $q .= app('test2')->increase();
        $q .= app('test2')->increase();
        $q .= app('test2')->increase();
        $this->showResult("Testing App bindings... ", $q, '123111');

        $arr = [
			['id' => 1, 'name' => 'Matias'],
			['id' => 2, 'name' => 'Natalia'],
			['id' => 3, 'name' => 'Micaela'],
			['id' => 4, 'name' => 'Agustina'],
			['id' => 5, 'name' => 'Lola'],
			['id' => 6, 'name' => 'Pepe'],
			['id' => 7, 'name' => 'Juan']
		];
        $q = collect($arr)->filter(function ($value) {
            return substr($value['name'], 0, 1) == 'M';
        })->all();
        $res = '[{"id":1,"name":"Matias"},{"id":3,"name":"Micaela"}]';
        $this->showResult("Testing collect() with filter and key/value... ", json_encode($q), $res);

        $arr = [
            'Matias',
			'Natalia',
			'Micaela',
			'Agustina',
			'Lola',
			'Pepe',
			'Juan'
        ];
        $q = collect($arr)->filter(function ($value) {
            return substr($value, 0, 1) == 'M';
        })->all();
        $res = '["Matias","Micaela"]';
        $this->showResult("Testing collect() with filter... ", json_encode($q), $res);

        $q = Producto::where('categoria_id', 1)->get()->implode(
            fn ($prod) => $prod->codigo . ':' . $prod->descripcion, ' :: '
        );

        $res = '1000:Windows 10 Home :: 1001:Windows 10 Professional :: 1002:Windows 10 Server :: 1010:Windows 11 Home :: 1011:Windows 11 Professional :: 1100:Norton Antivirus :: 1101:Avast Antivirus :: 1102:Symantec Antivirus :: 1200:Adobe Photoshop :: 1201:Adobe Aftereffects :: 1202:Adobe Illustrator :: 1300:Autodesk Autocad';
        $this->showResult("Testing Collection implode() with annonymous function... ", $q, $res);

        $q = Producto::where('categoria_id', 1)->get()->implode(
            function($prod) { return $prod->codigo . ':' . $prod->descripcion; }, ' :: '
        );
        $this->showResult("Testing Collection implode() with arrow function... ", $q, $res);
        
        $config = CoreLoader::loadConfigFile(_DIR_.'config/database.php');
        $default = $config['default']; 
        $driver = $config['connections'][$default]['driver'];

        Test::truncate();
        $q = DB::transaction( function() {

            Test::create([
                'name' => 'Primero',
                'descripcion' => 'Primer test',
                'email' => 'primero@test.com'
            ]);

            Test::create([
                'name' => 'Segundo',
                'descripcion' => 'Segundo test',
                'email' => 'segundo@test.com'
            ]);

        });
        $this->showResult("Testing DB transaction()... ", $q, true);
    
    
        Test::truncate();
        $q = DB::transaction( function() {

            Test::create([
                'name' => 'Primero',
                'descripcion' => 'Primer test',
                'email' => 'primero@test.com'
            ]);

            Test::create([
                'namea' => 'Segundo',
                'descripcion' => 'Segundo test',
                'email' => 'segundo@test.com'
            ]);

        });
        $this->showResult("Testing DB transaction() with failed insert... ", $q, false);

        
        $q = Producto::search('codigo', 100)->get()->toArray();
        $res = '[{"codigo":"1000","descripcion":"Windows 10 Home","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 14:57:17","test":"pepe"},{"codigo":"1001","descripcion":"Windows 10 Professional","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2023-01-02 18:05:57","test":"pepe"},{"codigo":"1002","descripcion":"Windows 10 Server","active":true,"categoria_id":1,"created_at":"2022-10-13","updated_at":"2022-12-03 11:43:54","test":"pepe"},{"codigo":"1100","descripcion":"Norton Antivirus","active":true,"categoria_id":1,"created_at":"2022-10-12","updated_at":null,"test":"pepe"},{"codigo":"2100","descripcion":"Placa Xfx Geforce 1050","active":true,"categoria_id":2,"created_at":"2022-10-12","updated_at":null,"test":"pepe"}]';
        $this->showResult("Testing Builder macro... ", json_encode($q), $res);



        Builder::macro('joinLateral', function($query, $as, $type='inner')
        {
            [$query, $bindings] = $this->createSub($query);
            $expression = 'lateral ('.$query.') as '.$this->grammar->wrapTable($as).' on true';
            $join = $this->newJoinClause($this, $type, new Expression($expression));
            $this->joins[] = $join;
            $this->addBinding($bindings, 'join');
            return $this;
        });
        $q = User::query()
            ->select(['users.name', 'latest_logins.logged_in_at'])
            ->joinLateral(
                Producto::whereColumn('logins.user_id', 'user.id')
                    ->latest('logins.logged_in_at')
                    ->limit(3),
                'latest_logins',
                'left'
            )->toSql();
        $res = "SELECT `users`.`name`, `latest_logins`.`logged_in_at` FROM `users` LEFT JOIN lateral (SELECT `productos`.* FROM `productos` WHERE `logins`.`user_id` = `user`.`id` ORDER BY `logins`.`logged_in_at` DESC LIMIT 3) as `latest_logins` on true";
        $this->showResult("Testing extended Builder macro... ", $q, $res);


        Collection::macro('toUpper', function () {
            return $this->map(function ($value) {
                return Str::upper($value);
            });
        });
        $collection = collect(['first', 'second']);
        $q = $collection->toUpper()->toArray();
        $res = '["FIRST","SECOND"]';
        $this->showResult("Testing Collection macro... ", json_encode($q), $res);

        Collection::macro('toLocale', function ($locale) {
            return $this->map(function ($value) use ($locale) {
                return $locale.":".$value;
            });
        });
        $collection = collect(['first', 'second']); 
        $q = $collection->toLocale('es')->toArray();
        $res = '["es:first","es:second"]';
        $this->showResult("Testing Collection macro with parameter... ", json_encode($q), $res);


        $val = Validator::make([
            'nombre' => 'Matias',
            'apellido' => 'Perez',
            'edad' => 18,
            'password' => 'micaela99'
        ],
        [
            'password' => Password::min(8)->mixedCase(),
            'apellido' => ['string', new TestRule()]
        ]);
        $val->passes();
        $q = $val->errors();
        $res = '{"password":"El password debe contener al menos una letra may\u00fascula y una min\u00fascula.","apellido":"The apellido must be uppercase."}';
        $this->showResult("Testing Password validator... ", json_encode($q), $res);

        $this->showEnd();

        //exit;
    }

    private function showStart()
    {
        echo "<table><thead><tr><th style='text-align:left;'>Description</th><th>Result</th></thead><tbody>";
    }

    private function showResult($text, $val1, $val2)
    {
        $val1 = str_replace('"', '', $val1);
        $val2 = str_replace('"', '', $val2);

        echo "<tr><td>$text</td><td>" .
            ($val1==$val2? "<span style='color:green;'>Passed</span>" : "<span style='color:red;'>Error</span>") .
            "</td></tr>";
    }

    public function showEnd()
    {
        echo "</tbody></table><br>All tests done<br>";
    }
}