<?php

class Validator
{
    protected $passed;

    protected $attributes;
    protected $rules;
    protected $validated = null;
    protected $errors;
    protected $messages = array();

    protected $stopOnFirstFailure = false;

    public function __construct($attributes, $rules, $messages = array())
    {
        $this->attributes = $attributes;
        $this->rules = $rules;
        $this->messages = $messages;
    }


    public static function make($attributes, $rules, $messages = array())
    {        
        $validator = new Validator($attributes, $rules, $messages); //self::validate($attributes, $rules);

        return $validator;
    }

    public function stopOnFirstFailure()
    {
        $this->stopOnFirstFailure = true;

        return $this;
    }

    private function setMessage($rule, $attributes)
    {
        if (isset($this->messages[$attributes['attribute']]))
        {
            $result = $this->messages[$attributes['attribute']];

            foreach ($attributes as $key => $val)
            {
                $result = str_replace(':'.$key, $val, $result);
            }

            $this->errors[$attributes['attribute']] = $result;
        }
        else
        {
            $this->errors[$attributes['attribute']] = __("validation." . $rule, $attributes);
        }
    }

    public function validate(/* $request, $arguments */)
    {
        //$instance = new Validator;
        //$instance->passed = true;
        //$instance->errors = array();
        $this->passed = true;
        $this->errors = array();

        $req_values = $this->attributes; //$request;

        

        foreach ($this->rules as $key => $validations)
        {
            if (is_string($validations)) {
                $validations = explode('|', $validations);
            }

            $ok = true;

            $canbenull = in_array('nullable', $validations);
            unset($validations['nullable']);
    
            foreach ($validations as $validation)
            {
                list($arg, $values) = explode(':', $validation);

                if ($arg=='bail') 
                {
                    $this->stopOnFirstFailure = true;
                }

                if ($arg=='required') 
                {
                    if ( !array_key_exists($key, $req_values) || (is_string($req_values[$key]) && strlen($req_values[$key])==0) )
                    {
                        $ok = false;
                        $this->setMessage('required', array('attribute' => $key));
                    }
                }

                else if ($arg=='present') 
                {
                    if ( !array_key_exists($key, $req_values) || (!isset($rreq_values[$key]) && !$canbenull) )
                    {
                        $ok = false;
                        $this->setMessage('present', array('attribute' => $key));
                    }
                }

                else if ($arg=='string') 
                {
                    if ( !array_key_exists($key, $req_values) || (!is_string($req_values[$key]) && !$canbenull) )
                    {
                        $ok = false;
                        $this->setMessage('string', array('attribute' => $key));
                    }
                }

                else if ($arg=='numeric') 
                {
                    if ( !array_key_exists($key, $req_values) || (!is_numeric($req_values[$key]) && !$canbenull) )
                    {
                        $ok = false;
                        $this->setMessage('numeric', array('attribute' => $key));
                        //$this->errors[$key] = __("validation.email", array('attribute' => $key));
                    }
                }

                else if ($arg=='min') 
                {
                    if (array_key_exists($key, $req_values))
                    {
                        if (is_string($req_values[$key]))
                        {
                            if (strlen($req_values[$key]) < $values)
                            {
                                $ok = false;
                                $this->setMessage('min.string', array('attribute' => $key, 'min' => $values));
                                //$this->errors[$key] = __("validation.min.string", array('attribute' => $key, 'min' => $values));
                            }
                        }
                        elseif (is_numeric($req_values[$key]))
                        {
                            if ($req_values[$key] < $values)
                            {
                                $ok = false;
                                $this->setMessage('min.numeric', array('attribute' => $key, 'min' => $values));
                                //$this->errors[$key] = __("validation.min.numeric", array('attribute' => $key, 'min' => $values));
                            }
                        }
                        elseif (is_array($req_values[$key]))
                        {
                            if (count($req_values[$key]) < $values)
                            {
                                $ok = false;
                                $this->setMessage('min.array', array('attribute' => $key, 'min' => $values));
                                //$this->errors[$key] = __("validation.min.array", array('attribute' => $key, 'min' => $values));
                            }
                        }
                        elseif (is_file($req_values[$key]))
                        {
                            if (round(filesize($req_values[$key])/1024) < $values)
                            {
                                $ok = false;
                                $this->setMessage('min.file', array('attribute' => $key, 'min' => $values));
                                //$this->errors[$key] = __("validation.min.file", array('attribute' => $key, 'min' => $values));
                            }
                        }
                    }
                }

                else if ($arg=='max') 
                {
                    if (array_key_exists($key, $req_values))
                    {
                        if (is_string($req_values[$key]))
                        {
                            if (strlen($req_values[$key]) > $values)
                            {
                                $ok = false;
                                $this->setMessage('max.string', array('attribute' => $key, 'max' => $values));
                                //$this->errors[$key] = __("validation.max.string", array('attribute' => $key, 'max' => $values));
                            }
                        }
                        elseif (is_numeric($req_values[$key]))
                        {
                            if ($req_values[$key] > $values)
                            {
                                $ok = false;
                                $this->setMessage('max.numeric', array('attribute' => $key, 'max' => $values));
                                //$this->errors[$key] = __("validation.max.numeric", array('attribute' => $key, 'max' => $values));
                            }
                        }
                        elseif (is_array($req_values[$key]))
                        {
                            if (count($req_values[$key]) > $values)
                            {
                                $ok = false;
                                $this->setMessage('max.array', array('attribute' => $key, 'max' => $values));
                                //$this->errors[$key] = __("validation.max.array", array('attribute' => $key, 'max' => $values));
                            }
                        }
                        elseif (is_file($req_values[$key]))
                        {
                            if (round(filesize($req_values[$key])/1024) > $values)
                            {
                                $ok = false;
                                $this->setMessage('max.file', array('attribute' => $key, 'max' => $values));
                                //$this->errors[$key] = __("validation.max.file", array('attribute' => $key, 'max' => $values));
                            }
                        }
                    }
                }

                else if ($arg=='unique') 
                {
                    if (array_key_exists($key, $req_values))
                    {
                        list($table, $column, $ignore) = explode(',', $values);
                        if (!$column) $column = $key;

                        $val = DB::table($table)->where($column, $req_values[$key])->first();

                        if ($val && $val->$column!=$ignore)
                        {
                            $ok = false;
                            $this->setMessage('unique', array('attribute' => $key));
                            //$this->errors[$key] = __("validation.unique", array('attribute' => $key));
                        }
                    }
                }

                else if ($arg=='boolean') 
                {
                    if (!array_key_exists($key, $req_values) || !in_array($req_values[$key], array(true, false, 1, 0, "1", "0"), true))
                    {
                        $ok = false;
                        $this->setMessage('boolean', array('attribute' => $key));
                        //$this->errors[$key] = __("validation.boolean", array('attribute' => $key));
                    }
                }

                else if ($arg=='accepted') 
                {
                    if (!array_key_exists($key, $req_values) || !in_array($req_values[$key], array("yes", "on", 1, true), true))
                    {
                        $ok = false;
                        $this->setMessage('accepted', array('attribute' => $key));
                        //$this->errors[$key] = __("validation.accepted", array('attribute' => $key));
                    }
                }

                else if ($arg=='declined') 
                {
                    if (!array_key_exists($key, $req_values) || !in_array($req_values[$key], array("no", "off", 0, false), true))
                    {
                        $ok = false;
                        $this->setMessage('declined', array('attribute' => $key));
                        //$this->errors[$key] = __("validation.declined", array('attribute' => $key));
                    }
                }

                else if ($arg=='email') 
                {
                    $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
                    if (!array_key_exists($key, $req_values) || !preg_match($regex, $req_values[$key]))
                    {
                        $ok = false;
                        $this->setMessage('email', array('attribute' => $key));
                        //$this->errors[$key] = __("validation.email", array('attribute' => $key));
                    }
                }
                

                if (!$ok)
                {
                    $this->passed = false;

                    if ($this->stopOnFirstFailure)
                        break;
                }
            }
            
            if ($this->stopOnFirstFailure && !$this->passed) break;

            if ($ok)
                $this->validated[$key] = $req_values[$key];

        }

        /* if (!self::$passed)
        {
            back()->withErrors($instance->errors)->showFinalResult();
            exit();
        } */

        return $this;
    }

    public function passes()
    {
        $this->validate();
        return $this->passed;
    }

    public function fails()
    {
        return !$this->passes();
    }

    public function validated()
    {
        return $this->validated;
    }

    public function errors()
    {
        return $this->errors;
    }


}