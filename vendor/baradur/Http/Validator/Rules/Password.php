<?php

class Password implements ValidationRule
{
    protected $validator;
    protected $data;

    protected $min = 8;
    protected $mixedCase = false;
    protected $letters = false;
    protected $numbers = false;
    protected $symbols = false;

    protected $messages = array();

    /* private $error_messages = array(
        'string' => 'The :attribute must be a string.',
        'min' => 'The :attribute must be at least :min characters.',
        'mixed' => 'The :attribute must contain at least one uppercase and one lowercase letter.',
        'letters' => 'The :attribute must contain at least one letter.',
        'symbols' => 'The :attribute must contain at least one symbol.',
        'numbers' => 'The :attribute must contain at least one number.',
    ); */

    public function __construct($min)
    {
        $this->min = max((int) $min, 1);
    }

    /**
     * Set the data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Sets the minimum size of the password.
     *
     * @param  int  $size
     * @return $this
     */
    public static function min($size)
    {
        return new Password($size);
    }

    /**
     * Makes the password require at least one uppercase and one lowercase letter.
     *
     * @return $this
     */
    public function mixedCase()
    {
        $this->mixedCase = true;

        return $this;
    }

    /**
     * Makes the password require at least one letter.
     *
     * @return $this
     */
    public function letters()
    {
        $this->letters = true;

        return $this;
    }

    /**
     * Makes the password require at least one number.
     *
     * @return $this
     */
    public function numbers()
    {
        $this->numbers = true;

        return $this;
    }

    /**
     * Makes the password require at least one symbol.
     *
     * @return $this
     */
    public function symbols()
    {
        $this->symbols = true;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function validate($attribute, $value, $fail)
    {
        $this->data = $attribute;

        $this->messages = array();
        
        if (! is_string($value)) {
            $this->messages[$attribute] = $this->getErrorMessage('string', array(':attribute' => $attribute));
        }

        if (strlen($value) < $this->min) {
            $this->messages[$attribute] = $this->getErrorMessage('min', array(':attribute' => $attribute, ':min' => $this->min));
        }

        if ($this->mixedCase && ! preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $value)) {
            $this->messages[$attribute] = $this->getErrorMessage('mixed', array(':attribute' => $attribute));
        }

        if ($this->letters && ! preg_match('/\pL/u', $value)) {
            $this->messages[$attribute] = $this->getErrorMessage('letters', array(':attribute' => $attribute));
        }

        if ($this->symbols && ! preg_match('/\p{Z}|\p{S}|\p{P}/u', $value)) {
            $this->messages[$attribute] = $this->getErrorMessage('symbols', array(':attribute' => $attribute));
        }

        if ($this->numbers && ! preg_match('/\pN/u', $value)) {
            $this->messages[$attribute] = $this->getErrorMessage('numbers', array(':attribute' => $attribute));
        }

        return $this;
    }


    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
    }

    /**
     * Get the translated password error message.
     *
     * @param  string  $key
     * @return string
     */
    protected function getErrorMessage($key, $replace=array())
    {
        if ($key=='string') {
            $res = __('validation.string');
        } elseif ($key=='min') {
            $res = __('validation.min.string');
        } else {
            $res = __('validation.password.'.$key);
        }
        
        foreach ($replace as $key => $val) {
            $res = str_replace($key, $val, $res);
        }

        return $res;
    }

}