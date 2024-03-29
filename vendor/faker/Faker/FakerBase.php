<?php

/*
 * This file is part of the Faker package.
 *
 * (c) 2011 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//namespace Faker;

/**
 * Base Faker class, consisting of helper methods for selecting and formatting fake data.
 *
 * @abstract
 */
abstract class FakerBase
{
    protected static function callbackNumerify()
    {
        return rand(0, 9);
    }

    protected static function callbackLetterify()
    {
        return chr(rand(97,122));
    }

    protected static function numerify($numberString)
    {
        return preg_replace_callback("/#/", 'FakerBase::callbackNumerify', $numberString);
    }

    protected static function letterify($letterString)
    {
        return preg_replace_callback("/\?/", 'FakerBase::callbackLetterify', $letterString);
    }

    protected static function bothify($string)
    {
        return self::letterify(self::numerify($string));
    }

    public static function pickOne(array $options)
    {
        return str_replace("'", "", $options[array_rand($options)]);
    }

    


}