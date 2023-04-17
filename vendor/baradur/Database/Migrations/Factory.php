<?php

abstract Class Factory
{
    protected $model;
    public $faker;
    public $count = 1;

    public function __construct()
    {
        $this->faker = new Faker;
    }

    private function __seed($values = array(), $persist = true)
    {
        Faker::resetUnique();
        Faker::setCounter($this->count);

        $count = $this->count;
        $model = new $this->model;

        $i = 0;
        $array = new Collection();

        $model->fillableOff();

        while ($i++ < $count) {
            $item = $this->definition();

            if (count($values) > 0) {
                foreach ($values as $key => $val) {
                    $item[$key] = $val;
                }
            }

            $array[] = $item;
        }

        return $model->seed($array, $persist);
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
     * @return Collection
     */
    public function create($values=array())
    {
        return $this->__seed($values, true);
    }

    /**
     * Seeds the Collection without persist\
     * the data in database 
     * 
     * @return Collection
     */
    public function make()
    {
        return $this->__seed(false);
    }


    abstract function definition();
    
}