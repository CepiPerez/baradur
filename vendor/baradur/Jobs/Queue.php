<?php

$working = true;

printf("\033[32m\n  To run queue in background use this command:\033[m  nohup php artisan queue:work &\n");

Artisan::info('Processing jobs from the [default] queue.');

while ($working)
{
    $res = DB::select("SHOW TABLES LIKE 'baradur_queue'");
    
    if (count($res) > 0) {
        Worker::checkQueue();
    }

    sleep(10);
}