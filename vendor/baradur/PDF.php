<?php

Class PDF 
{

    private static function generate($filename, $view)
    {
        $folder = _DIR_.'/../../storage/app/public/';

        Storage::put($filename.'.html', $view);
        $command = env('PDF_BIN').' '.$folder.$filename.'.html '.$folder.$filename.'.pdf';
        
        shell_exec($command);

        Storage::delete($filename.'.html', $view);

        return $folder.$filename.'.pdf';
    }


    public static function download($filename, $view)
    {
        $res = self::generate($filename, $view);        
        return response($res, 200, 'pdf:download', $filename.'.pdf');
    }

    public static function inline($filename, $view)
    {
        $res = self::generate($filename, $view);        
        return response($res, 200, 'pdf:inline', $filename.'.pdf');
    }


}