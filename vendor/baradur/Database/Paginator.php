<?php

class Paginator extends Collection
{
    protected static $style = 'tailwind';
	//protected $pagination;

    protected $pageName = 'page';

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
	public function setPagination($val)
	{
		//$this->pagination = $val;
        $this->first = $val['first']; 
        $this->last = $val['last']; 
        $this->previous = $val['previous'];
        $this->next = $val['next'];
        $this->query = $val['query'];
        $this->meta = $val['meta'];
	}

	# Gets pagination
	public function pagination()
	{
		//return $this->pagination;
        return array(
            'first' => $this->first, 
            'last'  => $this->last, 
            'previous' => $this->previous,
            'next' => $this->next,
            'query' => $this->query,
            'meta' => $this->meta
        );
	}

    public function setPageName($pageName)
    {
        $this->pageName = $pageName;
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

    /**
     * Builds pagination links in View
     * 
     * @return string
     */
    public function links()
    {
        if ($this->meta['last_page']==1) {
            return null;
        }

        if (Paginator::style()!='tailwind') {
            return View::loadTemplate('layouts/pagination-bootstrap4', array('paginator' => $this));
        }

        return View::loadTemplate('layouts/pagination-tailwind', array('paginator' => $this));
    }

    /**
     * Adds parameters to pagination links
     * 
     * @return Collection
     */
    public function appends($params=array())
    {
        unset($params['ruta']);
        unset($params[$this->pageName]);

        if (count($params)>0) {

            $str = http_build_query($params);

            $this->query = $str;
            
            if (isset($this->first)) {
                $this->first = $str . '&' . $this->first;
            }

            if (isset($this->previous)) {
                $this->previous = $str . '&' . $this->previous;
            }

            if (isset($this->next)) {
                $this->next = $str . '&' . $this->next;
            }

            if (isset($this->last)) {
                $this->last = $str . '&' . $this->last;
            }
        }

        return $this;
    }

    /**
     * Appends all of the current request's query string values to the pagination links
     * 
     * @return Collection
     */
    public function withQueryString()
    {
        $this->appends(request()->query());
        return $this;
    }

}