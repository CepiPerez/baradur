<?php

abstract Class Factory
{

    protected $model;
    public $faker;
    protected $count;

    public function __construct()
    {
        $this->faker = new Faker;
        
    }


    /**
     * Sets the factory number of models
     * 
     * @return Factory
     */
    public function count($nums)
    {
        $this->count = $nums;
        return $this;
    }

    /**
     * Seeds the Collection and persist\
     * the data in database 
     * 
     * @return Factory
     */
    public function create()
    {
        $count = $this->count;
        $model = $this->model;

        $i = 0;
        $array = new Collection($model);
        $model::fillableOff();

        while ($i++ < $count)
        {
            $array[] = $this->definition();
        }

        return $model::seed($array, true);
         
    }

    /**
     * Seeds the Collection without persist\
     * the data in database 
     * 
     * @return Factory
     */
    public function make()
    {
        $count = $this->count;
        $model = $this->model;

        $i = 0;
        $array = new Collection($model);
        $model::fillableOff();

        while ($i++ < $count)
        {
            $array[] = $this->definition();
        }

        return $model::seed($array, false);
         
    }


    abstract function definition();

    


}