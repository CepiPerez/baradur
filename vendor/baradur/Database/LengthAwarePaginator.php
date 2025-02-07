<?php

class LengthAwarePaginator extends Collection
{
    protected $options;

    protected static $currentPathResolver;

    protected static $style = 'tailwind';
    protected $pageName = 'page';
    public $first, $last, $previous, $next;
    public $query;
    public $meta;

    /**
     * Resolve the current page or return the default value.
     *
     * @param  string  $pageName
     * @param  int  $default
     * @return int
     */
    public static function resolveCurrentPage($pageName = 'page', $default = 1)
    {
        if (isset(request()->$pageName)) {
            return (int) request()->$pageName;
        }

        return $default;
    }

    /**
     * Determine if the given value is a valid page number.
     *
     * @param  int  $page
     * @return bool
     */
    protected function isValidPageNumber($page)
    {
        return $page >= 1 && is_integer($page);
    }

    /**
     * Get the current page.
     *
     * @return int
     */
    public function currentPage()
    {
        return $this->meta['current'];
    }

    /**
     * Get the URL for the previous page.
     *
     * @return string|null
     */
    public function previousPageUrl()
    {
        if ($this->currentPage() > 1) {
            return $this->url($this->currentPage() - 1);
        }
    }

    /**
     * Get the number of the first item in the slice.
     *
     * @return int|null
     */
    public function firstItem()
    {
        return count($this->items) > 0 ? ($this->currentPage - 1) * $this->perPage + 1 : null;
    }

    /**
     * Get the number of the last item in the slice.
     *
     * @return int|null
     */
    public function lastItem()
    {
        return count($this->items) > 0 ? $this->firstItem() + $this->count() - 1 : null;
    }

    /**
     * Get the number of items shown per page.
     *
     * @return int
     */
    public function perPage()
    {
        return $this->perPage;
    }

    /**
     * Get the base path for paginator generated URLs.
     *
     * @return string|null
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Build the full fragment portion of a URL.
     *
     * @return string
     */
    protected function buildFragment()
    {
        return $this->fragment ? '#' . $this->fragment : '';
    }

    /**
     * Get the URL for a given page number.
     *
     * @param  int  $page
     * @return string
     */
    public function url($page)
    {
        //return $this->meta['path'] . '?' . $this->query;

        if ($page <= 0) {
            $page = 1;
        }

        // If we have any extra query string key / value pairs that need to be added
        // onto the URL, we will put them in query string form and then attach it
        // to the URL. This allows for extra information like sortings storage.
        $parameters = [$this->pageName => $page];

        if (count($this->query) > 0) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path()
            . (str_contains($this->path(), '?') ? '&' : '?')
            . Arr::query($parameters)
            . $this->buildFragment();
    }

    /**
     * Resolve the current request path or return the default value.
     *
     * @param  string  $default
     * @return string
     */
    public static function resolveCurrentPath($default = '/')
    {
        if (isset(self::$currentPathResolver)) {
            return call_user_func(self::$currentPathResolver);
        }

        return $default;
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


    /**
     * Create a new paginator instance.
     *
     * @param  Collection $items
     * @param  int  $total
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options  (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($items, $total, $perPage, $currentPage = null, array $options = array())
    {
        if (!isset($options['pageName'])) {
            $options['pageName'] = 'page';
        }

        $this->options = $options;

        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        self::$style = Paginator::style();

        /* $this->total = $total;
        $this->perPage = (int) $perPage;
        $this->lastPage = max((int) ceil($total / $perPage), 1);
        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;
        $this->currentPage = $this->setCurrentPage($currentPage, $this->pageName);
        $this->items = $items instanceof Collection ? $items : new Collection($items); */

        $offset = ($currentPage - 1) * $perPage;
        $pages = ceil($total / $perPage);

        $this->items = $items;

        $this->first = $currentPage <= 1 ? null : $options['pageName'] . '=1';
        $this->last = $currentPage == $pages ? null : $options['pageName'] . '=' . $pages;
        $this->previous = $currentPage <= 1 ? null : $options['pageName'] . '=' . ($currentPage - 1);
        $this->next = $currentPage == $pages ? null : $options['pageName'] . '=' . ($currentPage + 1);

        $meta = array();
        $meta['current'] = $currentPage;
        $meta['from'] = $offset + 1;
        $meta['last_page'] = $pages;
        $meta['path'] = config('app.url') . '/' . request()->route->url;
        $meta['per_page'] = $perPage;
        $meta['to'] = $total < ($perPage * $currentPage) ? $total : ($perPage * $currentPage);
        $meta['total'] = $total;

        $this->meta = $meta;
    }

    /**
     * Get the current page for the request.
     *
     * @param  int  $currentPage
     * @param  string  $pageName
     * @return int
     */
    protected function setCurrentPage($currentPage, $pageName)
    {
        $currentPage = $currentPage ? $currentPage : self::resolveCurrentPage($pageName);

        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }

    /**
     * Builds pagination links in View
     * 
     * @return string
     */
    public function links()
    {
        if ($this->meta['last_page'] == 1) {
            return null;
        }

        if (Paginator::style() != 'tailwind') {
            return View::loadTemplate('layouts/pagination-bootstrap4', array('paginator' => $this));
        }

        return View::loadTemplate('layouts/pagination-tailwind', array('paginator' => $this));
    }

    /**
     * Get the paginator links as a collection (for JSON responses).
     *
     * @return \Illuminate\Support\Collection
     */
    public function linkCollection()
    {
        return collect($this->meta);
    }


    /**
     * Get the total number of items being paginated.
     *
     * @return int
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * Determine if there are more items in the data source.
     *
     * @return bool
     */
    public function hasMorePages()
    {
        return $this->next !== null;
    }

    /**
     * Get the URL for the next page.
     *
     * @return string|null
     */
    public function nextPageUrl()
    {
        if (!$this->next) return null;

        return $this->meta['path'] . '?' . $this->next;
    }

    /**
     * Get the last page.
     *
     * @return int
     */
    public function lastPage()
    {
        return $this->last;
    }


    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Adds parameters to pagination links
     * 
     * @return Collection
     */
    public function appends($params = array())
    {
        if ($params instanceof Request) {
            $params = $params->all();
        }

        unset($params['ruta']);
        unset($params[$this->pageName]);

        if (count($params) > 0) {

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
}
