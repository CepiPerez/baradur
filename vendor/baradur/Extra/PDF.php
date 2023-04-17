<?php

Class PDF 
{

    private static function generate($filename, $view, $landscape=false, $zoom="1")
    {
        $folder = _DIR_ . config('pdf.path') . '/';

        $folder = str_replace('//', '/', $folder);
        
        @unlink($folder.$filename.'.html');
        file_put_contents($folder.$filename.'.html', $view);
        chmod($folder.$filename.'.html', 0777);

        $command = config('pdf.bin').' ';

        if ($landscape) 
            $command .= '-O Landscape ';

        if ($zoom!="1") 
            $command .= '--zoom ' . $zoom . ' ';

        $command .= $folder.$filename.'.html '.$folder.$filename.'.pdf';

        @unlink($folder.$filename.'.pdf');

        //var_dump($command); die();
        
        shell_exec($command);

        @unlink($folder.$filename.'.html');

        if (!file_exists($folder.$filename.'.pdf')) {
            throw new Exception("Error creating PDF file. Check binary configuration.");
        }

        chmod($folder.$filename.'.pdf', 0777);

        //unlink($folder.$filename.'.html');

        //Storage::delete($filename.'.html', $view);

        return $folder.$filename.'.pdf';
    }


    public static function download($filename, $view, $landscape=false, $zoom="1")
    {
        $res = self::generate($filename, $view, $landscape, $zoom);

        $headers = array();
        $header['content-Transfer-Encoding'] = 'binary';
        $header['Accept-Ranges'] = 'bytes';

        return response()->download($res, $filename.'.pdf', $headers);
    }

    public static function inline($filename, $view, $landscape=false, $zoom="1")
    {
        $res = self::generate($filename, $view, $landscape, $zoom);

        $headers = array();
        $header['content-Transfer-Encoding'] = 'binary';
        $header['Accept-Ranges'] = 'bytes';

        return response()->file($res, $headers);
    }


}