<?php

class Kernel extends ConsoleKernel
{

    protected $commands = [

    ];

    
    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/commands');

        require base_path('routes/console.php');
    }
}
