<?php

Class Seeder
{

    public function call($class)
    {
        printf("Seeding: ".$class."\n");
        $seeder = new $class;
        $seeder->run();
    }


}