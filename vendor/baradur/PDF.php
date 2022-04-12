<?php

Class PDF 
{

    private static function generate($filename, $view)
    {
        $folder = 'storage/';

        file_put_contents($folder.$filename.'.html', $view);
        $command = PDF_BIN.' '.$folder.$filename.'.html '.$folder.$filename.'.pdf';
        
        /* $res = proc_open($command,
        array(
          array("pipe","r"),
          array("pipe","w"),
          array("pipe","w")
        ),
        $pipes);
        sleep(3); */

        shell_exec($command);

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