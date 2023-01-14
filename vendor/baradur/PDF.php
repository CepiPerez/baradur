<?php

Class PDF 
{

    private static function generate($filename, $view, $landscape=false, $zoom="1")
    {
        $folder = _DIR_.'storage/app/public/';

        Storage::put($filename.'.html', $view);

        $command = env('PDF_BIN').' ';

        if ($landscape) 
            $command .= '-O Landscape ';

        if ($zoom!="1") 
            $command .= '--zoom ' . $zoom . ' ';

        $command .= $folder.$filename.'.html '.$folder.$filename.'.pdf';

        Storage::delete($filename.'.pdf', $view);

        //var_dump($command); die();
        
        shell_exec($command);

        //Storage::delete($filename.'.html', $view);

        return $folder.$filename.'.pdf';
    }


    public static function download($filename, $view, $landscape=false, $zoom="1")
    {
        $res = self::generate($filename, $view, $landscape, $zoom);

        return response($res, 200, 'pdf:download', $filename.'.pdf');
    }

    public static function inline($filename, $view, $landscape=false, $zoom="1")
    {
        $res = self::generate($filename, $view, $landscape, $zoom);

        return response($res, 200, 'pdf:inline', $filename.'.pdf');
    }


}