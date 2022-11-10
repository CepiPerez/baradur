<?php

Class Worker
{
    

    public static function checkQueue()
    {
        printf("Checking queue... ");

        $res = DB::table('baradur_queue')->where('status', 0)->first();

        if ($res)
        {
            printf("processing job...\n");
            #print_r(unserialize($res->content));
            
            $mail = unserialize($res->content);
            
            if (self::send($mail))
            {
                printf("done!\n");
                DB::query('UPDATE baradur_queue set status=1 where id='.$res->id);
            }
            else
            {
                printf("error sending message\n");
                DB::query('UPDATE baradur_queue set status=2 where id='.$res->id);
            }
        }
        else
        {
            printf("no jobs found\n");
        }

    }

    private static function send($mail)
    {
        $default = Helpers::config('mail.default');
        printf("Sending mail through $default... ");
        
        if ($default=='smtp')
            return $mail->sendSmtp();

        else if ($default=='sendmail')
            return $mail->sendMail();

        return false;

    }

    
}