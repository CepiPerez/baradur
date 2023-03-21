<?php

interface ValidationRule
{
    public function validate($attribute, $value, $fail);

}