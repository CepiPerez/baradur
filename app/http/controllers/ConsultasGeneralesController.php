<?php

class ConsultasGeneralesController extends Controller
{

    private function mes($val)
    { 
        $meses = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                      'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $val = (int)$val;
        return $meses[$val-1];
    }

    private function getImage($val)
    {
        if (file_exists("http://192.168.1.242/Consulta_generales/design/image/fotos_perfil/".$val)) 
        {
            return 'http://192.168.1.242/Consulta_generales/design/image/fotos_perfil/'.$val;
        }
        else
        {
            return "http://192.168.1.242/Consulta_generales/design/image/default_avatar_male.png";
        }
    }


    public function inicio()
    {
        $buscar = $_GET['buscar'];

        $title = "TEST";

        if (isset($buscar) && $buscar=='')
            return redirect('consultas');

        $data = Agentes::select(['legajo', 'nro_doc', 'nombre'])->orderBy('nombre');
        
        if (isset($buscar) && $buscar!='')
            $data = $data->where('legajo', 'LIKE', "%$buscar%")
                ->orWhere('nro_doc', 'LIKE', "%$buscar%")
                ->orWhere('nombre', 'LIKE', "%$buscar%");

        $data = $data->paginate(20);

        $breadcrumb = array(
            'Inicio' => '/',
            'Consultas' => '#'
        );
        //var_dump($data);
        return view('agentes', compact('title', 'breadcrumb', 'data', 'buscar'));
    }


    public function veragente($dni)
    {
        $data = Agentes::with(['familiares', 'cursos', 'menciones',
                'sanciones', 'antiguedad', 'licencias', 'rlicencias'])
                ->where('nro_doc', $dni)->first();

        foreach ($data->licencias as $l)
        {
            $l->P18FDE = substr($l->P18FDE,-2) .'-'. substr($l->P18FDE,4,2) .'-'. substr($l->P18FDE,0, 4);
            $l->P18FHA = substr($l->P18FHA,-2) .'-'. substr($l->P18FHA,4,2) .'-'. substr($l->P18FHA,0, 4);
        }
        
        foreach ($data->rlicencias as $rl)
        {
            $rl->mes = $this->mes($rl->P20MES);
        }

        foreach ($data->antiguedad as $al)
        {
            $al->mes = $this->mes($rl->P20MES);
            $al->organismo = $al->ANTT51;
            $al->ingreso = substr($al->ANTU48,-2) .'-'. substr($al->ANTU48,4,2) .'-'. substr($al->ANTU48,0, 4);
            $al->egreso = substr($al->ANTU49,-2) .'-'. substr($al->ANTU49,4,2) .'-'. substr($al->ANTU49,0, 4);
            if ($al->egreso=='0--0') $al->egreso = '';
            $al->constancia = $al->ANTU28;
            $al->certificado = $al->ANTA10;
        }
    

        $breadcrumb = array(
            'Inicio' => '/',
            'Consultas' => '/consultas',
            'Datos del agente' => '#'
        );


        $data->imglogin = $this->getImage($data->nro_doc);

        //$data->licencias = $lic;
        //$data->rlicencias = $rlic;

        return view('agente', compact('breadcrumb', 'data'));

    }



}