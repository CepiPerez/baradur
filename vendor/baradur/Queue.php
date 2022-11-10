<?php

$database = new Connector(env('DB_LOCAL_HOST'), env('DB_USER'), env('DB_PASSWORD'), env('DB_NAME'), env('DB_PORT'));


$working = true;

printf("\033[32mProcessing jobs from the [default] queue. \033[m\n");
printf("\033[32mTo run queue in background use this command:\033[m\n");
printf("  nohup php artisan queue:work &\n\n");

while ($working)
{
    $res = DB::table('information_schema.tables')->selectRaw('table_name')
        ->whereRaw("table_schema = '".env('DB_NAME')."' AND table_name = 'baradur_queue'")
        ->first();
    
    if ($res)
    {
        Worker::checkQueue();
    }
    else
    {
        printf("Checking queue... no jobs found\n");
    }

    sleep(10);
}