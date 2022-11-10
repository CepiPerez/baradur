<?php

class Paginator
{
    protected static $style = 'tailwind';
	protected static $pagination;

    public static function style()
    {
        return self::$style;
    }

    public static function useBootstrapFour()
    {
        self::$style = 'bootstrap4';
    }

    public static function useBootstrapFive()
    {
        self::$style = 'bootstrap5';
    }

    # Sets pagination
	public static function setPagination($val)
	{
		self::$pagination = $val;
	}

	# Gets pagination
	public static function pagination()
	{
		return self::$pagination;
	}

}