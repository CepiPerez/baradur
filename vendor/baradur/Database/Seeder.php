<?php

Class Seeder
{

    public function call($class)
    {
        $startTime = microtime(true);
        printf("\e[38;5;214mSeeding:\e[m ".$class."\n");
        $seeder = new $class;
        $seeder->run();
        $endTime = microtime(true);
        $time =($endTime-$startTime)*1000;
        printf("\e[38;5;40mSeeded: \e[m ".$class." (". round($time, 2) ."ms)\n");
    }


}