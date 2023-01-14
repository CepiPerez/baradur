<?php

class Paginator
{
    protected static $style = 'tailwind';
	protected static $pagination;

    public $first, $last, $previous, $next;
    public $query;
    public $meta;

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


    public function currentPage()
    {
        return $this->meta['current'];
    }

    public function previousPageUrl()
    {
        if (!$this->previous) return null;

        return $this->meta['path'] . '?' . $this->previous;
    }

    public function nextPageUrl()
    {
        if (!$this->next) return null;

        return $this->meta['path'] . '?' . $this->next;
    }

    public function firstItem()
    {
        return $this->meta['from'];
    }

    public function lastItem()
    {
        return $this->meta['to'];
    }

    public function hasMorePages()
    {
        return $this->next!==null;
    }

    public function url()
    {
        return $this->meta['path'] . '?' . $this->query;
    }

}