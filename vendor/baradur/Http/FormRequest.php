<?php

Class FormRequest extends Request
{

    protected function prepareForValidation()
    {
        return request()->all();
    }

    public function validateRules($rules)
    {
        $this->post = $this->prepareForValidation();
        request()->validate($rules);
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
    
}