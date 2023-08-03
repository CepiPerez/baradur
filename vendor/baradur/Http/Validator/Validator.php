<?php

class Validator
{
    protected $passed;

    protected $data;
    protected $attributes;
    protected $rules;
    protected $validated = null;
    protected $errors;
    protected $messages = array();

    protected $stopOnFirstFailure = false;

    public function __construct($data, $rules, $messages = array(), $attributes = array())
    {
        $this->data = $data;
        $this->attributes = $attributes;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make($data, $rules, $messages = array(), $attributes = array())
    {        
        $validator = new Validator($data, $rules, $messages, $attributes);

        return $validator;
    }

    public function stopOnFirstFailure()
    {
        $this->stopOnFirstFailure = true;

        return $this;
    }

    private function setMessage($rule, $attributes)
    {
        foreach ($this->attributes as $key => $val) {
            if ($attributes['attribute']==$key) {
                $attributes['attribute'] = $val;
            }
        }
        
        if (isset($this->messages[$attributes['attribute']])) {
            $result = $this->messages[$attributes['attribute']];
                        
            foreach ($attributes as $key => $val) {
                $result = str_replace(':'.$key, $val, $result);
            }
            
            $this->errors[$attributes['attribute']] = $result;
        } else {
            $this->errors[$attributes['attribute']] = __("validation." . $rule, $attributes);
        }
    }

    public function validate(/* $request, $arguments */)
    {
        global $_class_list;
        //$instance = new Validator;
        //$instance->passed = true;
        //$instance->errors = array();
        $this->passed = true;
        $this->errors = array();

        $req_values = $this->data;

        foreach ($this->rules as $key => $validations) {

            if (is_string($validations)) {
                $validations = explode('|', $validations);
            }

            if (!is_array($validations)) {
                $validations = array($validations);
            }

            $ok = true;

            $canbenull = in_array('nullable', $validations);
            unset($validations['nullable']);
    
            foreach ($validations as $validation) {

                if (is_object($validation) && get_class($validation)!='RuleItem'  
                    && isset($_class_list[get_class($validation)])) {

                    $res = $validation->validate($key, $req_values[$key], null);

                    if ($validation instanceof Password && count($res->message()) > 0) {
                        $this->errors = array_merge($this->errors, $res->message());
                        $ok = false;
                    }

                    elseif ($res) {
                        $this->errors[$key] = str_replace(':attribute', $key, $res);
                        $ok = false;
                    }

                } else {

                    if (is_string($validation)) {
                        list($arg, $values) = explode(':', $validation);
                    } else {
                        $arg = $validation;
                    }
    
                    if ($arg=='bail')  {
                        $this->stopOnFirstFailure = true;
                    }
    
                    if ($arg=='required') {
                        if ( !array_key_exists($key, $req_values) || (is_string($req_values[$key]) && strlen($req_values[$key])==0) ) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        }
                    }
    
                    else if ($arg=='present') {
                        if ( !array_key_exists($key, $req_values) || (!isset($rreq_values[$key]) && !$canbenull) ) {
                            $ok = false;
                            $this->setMessage('present', array('attribute' => $key));
                        }
                    }
    
                    else if ($arg=='string') {
                        if ( !array_key_exists($key, $req_values) || (!is_string($req_values[$key]) && !$canbenull) ) {
                            $ok = false;
                            $this->setMessage('string', array('attribute' => $key));
                        }
                    }
    
                    else if ($arg=='numeric') {
                        if ( !array_key_exists($key, $req_values) || (!is_numeric($req_values[$key]) && !$canbenull) ) {
                            $ok = false;
                            $this->setMessage('numeric', array('attribute' => $key));
                            //$this->errors[$key] = __("validation.email", array('attribute' => $key));
                        }
                    }
    
                    else if ($arg=='min') {
                        if (array_key_exists($key, $req_values)) {
                            if (is_string($req_values[$key])) {
                                if (strlen($req_values[$key]) < $values) {
                                    $ok = false;
                                    $this->setMessage('min.string', array('attribute' => $key, 'min' => $values));
                                }
                            } elseif (is_numeric($req_values[$key])) {
                                if ($req_values[$key] < $values) {
                                    $ok = false;
                                    $this->setMessage('min.numeric', array('attribute' => $key, 'min' => $values));
                                }
                            } elseif (is_array($req_values[$key])) {
                                if (count($req_values[$key]) < $values) {
                                    $ok = false;
                                    $this->setMessage('min.array', array('attribute' => $key, 'min' => $values));
                                }
                            } elseif (is_file($req_values[$key])) {
                                if (round(filesize($req_values[$key])/1024) < $values) {
                                    $ok = false;
                                    $this->setMessage('min.file', array('attribute' => $key, 'min' => $values));
                                }
                            }
                        }
                    }
    
                    else if ($arg=='max') {
                        if (array_key_exists($key, $req_values)) {
                            if (is_string($req_values[$key])) {
                                if (strlen($req_values[$key]) > $values) {
                                    $ok = false;
                                    $this->setMessage('max.string', array('attribute' => $key, 'max' => $values));
                                }
                            } elseif (is_numeric($req_values[$key])) {
                                if ($req_values[$key] > $values) {
                                    $ok = false;
                                    $this->setMessage('max.numeric', array('attribute' => $key, 'max' => $values));
                                }
                            } elseif (is_array($req_values[$key])) {
                                if (count($req_values[$key]) > $values) {
                                    $ok = false;
                                    $this->setMessage('max.array', array('attribute' => $key, 'max' => $values));
                                }
                            } elseif (is_file($req_values[$key])) {
                                if (round(filesize($req_values[$key])/1024) > $values) {
                                    $ok = false;
                                    $this->setMessage('max.file', array('attribute' => $key, 'max' => $values));
                                }
                            }
                        }
                    }
    
                    else if ($arg=='unique')  {
                        if (array_key_exists($key, $req_values)) {
                            list($table, $column, $ignore) = explode(',', $values);
                            if (!$column) $column = $key;
    
                            $val = DB::table($table)->where($column, $req_values[$key])->first();
    
                            if ($val && $val->$column!=$ignore) {
                                $ok = false;
                                $this->setMessage('unique', array('attribute' => $key));
                            }
                        }
                    }

                    else if ($arg=='array') {
                        if (!array_key_exists($key, $req_values) || !is_array($req_values[$key])) {
                            $ok = false;
                            $this->setMessage('array', array('attribute' => $key));
                        }
                        if ($values) {
                            $values = explode(',', $values);
                            $count = array_diff_key(array_keys($req_values[$key]), $values);
                            if (count($count) > 0) {
                                $ok = false;
                                $this->setMessage('array', array('attribute' => $key));
                            }
                        }
                    }
    
                    else if ($arg=='boolean') {
                        if (!array_key_exists($key, $req_values) || !in_array($req_values[$key], array(true, false, 1, 0, "1", "0"), true)) {
                            $ok = false;
                            $this->setMessage('boolean', array('attribute' => $key));
                        }
                    }
    
                    else if ($arg=='accepted') {
                        if (!array_key_exists($key, $req_values) || !in_array($req_values[$key], array("yes", "on", 1, true), true)) {
                            $ok = false;
                            $this->setMessage('accepted', array('attribute' => $key));
                        }
                    }
    
                    else if ($arg=='declined') {
                        if (!array_key_exists($key, $req_values) || !in_array($req_values[$key], array("no", "off", 0, false), true)) {
                            $ok = false;
                            $this->setMessage('declined', array('attribute' => $key));
                        }
                    }
    
                    else if ($arg=='email') {
                        $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';

                        if (!array_key_exists($key, $req_values) || !preg_match($regex, $req_values[$key])) {
                            $ok = false;
                            $this->setMessage('email', array('attribute' => $key));
                        }
                    }

                    else if ($arg=='date') {
                        if (!array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        } elseif (!strtotime($req_values[$key])) {
                            $ok = false;
                            $this->setMessage('date', array('attribute' => $key));
                        }
                    }

                    else if ($arg=='date_equals') {
                        $compare = $values;
                        if (array_key_exists($values, $req_values)) {
                            $compare = $req_values[$values];
                        }
                        if (!array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        } elseif (strtotime($req_values[$key]) != strtotime($compare)) {
                            $ok = false;
                            $this->setMessage('date_equals', array('attribute' => $key, 'date' => $values));
                        }
                    }

                    else if ($arg=='after') {
                        $compare = $values;
                        if (array_key_exists($values, $req_values)) {
                            $compare = $req_values[$values];
                        }
                        if (!array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        } elseif (strtotime($req_values[$key]) < strtotime($compare)) {
                            $ok = false;
                            $this->setMessage('after', array('attribute' => $key, 'date' => $values));
                        }
                    }

                    else if ($arg=='after_or_equal') {
                        $compare = $values;
                        if (array_key_exists($values, $req_values)) {
                            $compare = $req_values[$values];
                        }
                        if (!array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        } elseif (strtotime($req_values[$key]) <= strtotime($compare)) {
                            $ok = false;
                            $this->setMessage('after_or_equal', array('attribute' => $key, 'date' => $values));
                        }
                    }

                    else if ($arg=='before') {
                        $compare = $values;
                        if (array_key_exists($values, $req_values)) {
                            $compare = $req_values[$values];
                        }
                        if (!array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        } elseif (strtotime($req_values[$key]) > strtotime($compare)) {
                            $ok = false;
                            $this->setMessage('before', array('attribute' => $key, 'date' => $values));
                        }
                    }

                    else if ($arg=='before_or_equal') {
                        $compare = $values;
                        if (array_key_exists($values, $req_values)) {
                            $compare = $req_values[$values];
                        }
                        if (!array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        } elseif (strtotime($req_values[$key]) >= strtotime($compare)) {
                            $ok = false;
                            $this->setMessage('before_or_equal', array('attribute' => $key, 'date' => $values));
                        }
                    }

                    else if ($arg=='between') {
                        list($min, $max) = explode(',', $values);
                        if (!array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        } else {
                            if (is_numeric($req_values[$key])) {
                                if ($req_values[$key] < $min || $req_values[$key] > $max) {
                                    $ok = false;
                                    $this->setMessage('between.numeric', array('attribute' => $key, 'min' => $min, 'max' => $max));
                                }        
                            } elseif (is_array($req_values[$key])) {
                                if (count($req_values[$key]) < $min || count($req_values[$key]) > $max) {
                                    $ok = false;
                                    $this->setMessage('between.array', array('attribute' => $key, 'min' => $min, 'max' => $max));
                                }        
                            } elseif (is_file($req_values[$key])) {
                                if (filesize($req_values[$key]) < $min || filesize($req_values[$key]) > $max) {
                                    $ok = false;
                                    $this->setMessage('between.file', array('attribute' => $key, 'min' => $min, 'max' => $max));
                                }        
                            } elseif (is_string($req_values[$key])) {
                                if (strlen($req_values[$key]) < $min || strlen($req_values[$key]) > $max) {
                                    $ok = false;
                                    $this->setMessage('between.string', array('attribute' => $key, 'min' => $min, 'max' => $max));
                                }
                            }
                        }
                        
                        
                    }

                    else if ($arg=='confirmed') {
                        if (!array_key_exists($key, $req_values) || 
                            !array_key_exists($key.'_confirmation', $req_values) ||
                            $req_values[$key]!=$req_values[$key.'_confirmation']) {
                            $ok = false;
                            $this->setMessage('confirmed', array('attribute' => $key));
                        }
                    }

                    else if ($arg=='decimal') {
                        if (!array_key_exists($key, $req_values)) { 
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        }
                        list($min, $max) = explode(',', $values);
                        $digits = (int) strpos(strrev($req_values[$key]), '.');
                        if ($digits < (int)$min) {
                            $ok = false;
                            $this->setMessage('decimal', array('attribute' => $key, 'decimal' => $min));
                        }
                        if ($max && $digits > (int)$max) {
                            $ok = false;
                            $this->setMessage('decimal', array('attribute' => $key, 'decimal' => $max));
                        }
                    }

                    else if ($arg=='url') {
                        if (!array_key_exists($key, $req_values)) { 
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        }
                        if (!Str::isUrl($req_values[$key])) {
                            $ok = false;
                            $this->setMessage('url', array('attribute' => $key));
                        }
                    }

                    else if ($arg=='uuid') {
                        if (!array_key_exists($key, $req_values)) { 
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        }
                        if (!Str::isUuid($req_values[$key])) {
                            $ok = false;
                            $this->setMessage('uuid', array('attribute' => $key));
                        }
                    }

                    else if ($arg=='ulid') {
                        if (!array_key_exists($key, $req_values)) { 
                            $ok = false;
                            $this->setMessage('required', array('attribute' => $key));
                        }
                        if (!Str::isUlid($req_values[$key])) {
                            $ok = false;
                            $this->setMessage('ulid', array('attribute' => $key));
                        }
                    }

                    else if ($arg=='prohibited_if') {
                        list($another_field, $value) = explode(',', $values);
                        if (blank($value)) {
                            if (array_key_exists($key, $req_values) && array_key_exists($another_field, $req_values)) {
                                $ok = false;
                                $this->setMessage('prohibited', array('attribute' => $key));
                            }
                        } elseif ($req_values[$another_field]==$value && array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('prohibited_if', array('attribute' => $key, 'other' => $another_field, 'value' => $value));
                        }
                    }

                    else if ($arg=='required_if') {
                        list($another_field, $value) = explode(',', $values);
                        if (blank($value)) {
                            if (!array_key_exists($key, $req_values) && array_key_exists($another_field, $req_values)) {
                                $ok = false;
                                $this->setMessage('required', array('attribute' => $key));
                            }
                        } elseif ($req_values[$another_field]==$value && !array_key_exists($key, $req_values)) {
                            $ok = false;
                            $this->setMessage('required_if', array('attribute' => $key, 'other' => $another_field, 'value' => $value));
                        }
                    }


                    else if (($arg instanceof RuleItem)) {
                        if ($arg->type=='in') {
                            if (!in_array($req_values[$key], $arg->value)) { 
                                $ok = false;
                                $this->setMessage('in', array('attribute' => $key));
                            }
                        }
                        elseif ($arg->type=='prohibited_if') {
                            if (is_closure($arg->value)) {
                                list($class, $method) = getCallbackFromString($arg->value);
		                        $value = call_user_func_array(array($class, $method), array());
                                if ($value  && array_key_exists($key, $req_values)) {
                                    $ok = false;
                                    $this->setMessage('prohibited', array('attribute' => $key));
                                }
                            } elseif ($arg->value  && array_key_exists($key, $req_values)) {
                                $ok = false;
                                $this->setMessage('prohibited', array('attribute' => $key));
                            }
                        }
                        elseif ($arg->type=='required_if') {
                            if (is_closure($arg->value)) {
                                list($class, $method) = getCallbackFromString($arg->value);
		                        $value = call_user_func_array(array($class, $method), array());
                                if ($value && !array_key_exists($key, $req_values)) {
                                    $ok = false;
                                    $this->setMessage('required', array('attribute' => $key));
                                }
                            } elseif ($arg->value && !array_key_exists($key, $req_values)) {
                                $ok = false;
                                $this->setMessage('required', array('attribute' => $key));
                            }
                        }
                        elseif ($arg->type=='unique') {
                            $value = null;
                            if ($arg->ignore instanceof Model) {
                                $k = $arg->ignore_column ? $arg->ignore_column : $arg->ignore->getKeyName();
                                $value = $arg->ignore->$k;
                            } else {
                                $value = $arg->ignore;
                            }                            
                            $col = $arg->column? $arg->column : $key;
                            $val = DB::table($arg->value)->where($col, $req_values[$key])->first();
                            if ($val && $val->$col!=$value) {
                                $ok = false;
                                $this->setMessage('unique', array('attribute' => $key));
                            }

                        }

                    }
                }

                if (!$ok) {
                    $this->passed = false;

                    if ($this->stopOnFirstFailure) {
                        break;
                    }
                }
            }
            
            if ($this->stopOnFirstFailure && !$this->passed) {
                break;
            }

            if ($ok) {
                $this->validated[$key] = $req_values[$key];
            }

        }

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