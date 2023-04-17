<?php

class MusicaController extends Controller
{

    public function inicio()
    {
        /* $data = Musica::select(['id', 'name'])->get();
        dd($data->loadExists('songs'));
        exit(); */


        $title = 'Musica';

        $data = Musica::with('playlists.songs.loves')
            ->get();
       
        $breadcrumb = array(
            'Inicio' => '/',
            'Musica' => '#'
        );

        return view('musica', compact('title', 'breadcrumb', 'data'));

    }

    


}

?>