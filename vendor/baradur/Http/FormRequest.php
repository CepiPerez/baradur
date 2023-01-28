<?php

Class FormRequest extends Request
{

    protected function prepareForValidation()
    {
        return request()->all();
    }

    public function validateRules()
    {
        $this->post = $this->prepareForValidation();
        request()->validate($this->rules());
    }

    public function validated()
    {
        return request()->validated();
    }

    public function merge($array = array())
    {
        foreach ($array as $key => $val)
        {
            request()->$key = $val;
        }
    }

    public function rules()
    {
        return array();
    }
    
}