<?php

Class Request
{
    public $_get = array();

    public function validate($arguments)
    {
        $pass = true;
        $stopOnFirstFail = false;
        $errors = array();

        foreach ($arguments as $key => $argument)
        {
            $validations = explode('|', $argument);
    
            foreach ($validations as $validation)
            {
                //echo "Validating: ".$key." : ".$validation."<br>";

                list($arg, $values) = explode(':', $validation);

                if ($arg=='bail') 
                {
                    $stopOnFirstFail = true;
                }

                else if ($arg=='required') 
                {
                    if ( !isset($this->$key) || strlen($this->$key)==0 )
                    {
                        $pass = false;
                        $errors[$key] = $key.' cannot be empty';
                    }
                }

                else if ($arg=='max') 
                {
                    if ( isset($this->$key) && is_string($this->$key) && strlen($this->$key)<=$values) continue;
                    elseif ( isset($this->$key) && $this->$key<=$values) continue;
                    else
                    {
                        $pass = false;
                        $errors[$key] = $key.' is too long';
                    }
                }

                else if ($arg=='unique') 
                {
                    list($table, $column, $ignore) = explode(',', $values);
                    if (!$column) $column = $key;

                    $value = $this->$key;

                    $val = DB::table($table)->where($column, $value)->first();
                    
                    if ($val && $val->$column!=$ignore)
                    {
                        $pass = false;
                        $errors[$key] = $key.' already exists';
                    }
                }

                if ($stopOnFirstFail && !$pass) break;
    
            }

            if ($stopOnFirstFail && !$pass) break;


        }

        if (!$pass)
        {
            back()->withErrors($errors)->showFinalResult();
            exit();
        }

        return $pass;
    }

    public function all()
    {
        $array = array();
        foreach ($this as $key => $val)
            $array[$key] = $val;
            
        return $array;
    }
    
    public function query()
    {
        return $this->_get;
    }

}
