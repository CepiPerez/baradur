<?php

Class FormRequest extends Request
{


    protected function prepareForValidation()
    {
        return $this->all();
    }

    protected function passedValidation()
    {
        //
    }

    public function validateRules()
    {
        $this->post = $this->prepareForValidation();

        $res = $this->validate($this->rules());

        if ($res) {
            $this->passedValidation();
        }
    }

    public function validated()
    {
        return $this->validated;
    }

    public function merge($array = array())
    {
        foreach ($array as $key => $val)
        {
            $this->$key = $val;
        }
    }

    public function rules()
    {
        return array();
    }
    
}