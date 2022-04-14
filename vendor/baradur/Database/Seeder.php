<?php

Class Seeder
{

    public function call($class)
    {
        $startTime = microtime(true);
        printf("Seeding: ".$class."\n");
        $seeder = new $class;
        $seeder->run();
        $endTime = microtime(true);
        $time =($endTime-$startTime)*1000;
        printf("Seeded: ".$class." (". round($time, 2) ."ms)\n");
    }


}