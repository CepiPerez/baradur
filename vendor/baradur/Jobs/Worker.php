<?php

Class Worker
{
    

    public static function checkQueue()
    {
        /* printf("Checking queue... "); */

        $res = DB::select('SELECT * from baradur_queue where status = 0')->first();

        if ($res)
        {
            $startTime = microtime(true);
            
            $mail = unserialize($res->content);

            $result = null;
            
            if (self::send($mail))
            {
                $result = 'DONE';
                DB::update('UPDATE baradur_queue set status=1 where id='.$res->id);
            }
            else
            {
                $result = 'ERROR';
                //DB::update('UPDATE baradur_queue set status=2 where id='.$res->id);
            }

            $endTime = microtime(true);
            $time = ($endTime-$startTime)*1000;

            if ($time > 1) {
                $time = number_format($time, 3) . "s";
            } else {
                $time = number_format($time *1000, 3) . "ms";
            }

            Artisan::lineInfo(get_class($mail), $result, $time);
        }
        /* else
        {
            printf("no jobs found\n");
        } */

    }

    private static function send($mail)
    {
        $default = Helpers::config('mail.default');
        
        if ($default=='smtp')
            return $mail->sendSmtp();

        else if ($default=='sendmail')
            return $mail->sendMail();

        return false;

    }

    
}