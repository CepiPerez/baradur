<?php

class Arr
{
    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value); // || $value instanceof ArrayAccess;
    }

    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param  array  $array
     * @param  string|int|float  $key
     * @param  mixed  $value
     * @return array
     */
    public static function add($array, $key, $value)
    {
        if (is_null(self::get($array, $key))) {
            self::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  iterable  $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = array();

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            if (is_assoc($values)) {
                foreach ($values as $key => $val) {
                    $results[] = $val;
                }
            } elseif (is_array($values)) {
                $results = array_merge($results, $values);
            } else {
                $results[] = $values;
            }
        }

        return $results;
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     *
     * @return array
     */
    public static function crossJoin()
    {
        $arrays = func_get_args();
        $results = array(array());

        foreach ($arrays as $index => $array) {
            $append = array();

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param  array  $array
     * @return array
     */
    public static function divide($array)
    {
        return array(array_keys($array), array_values($array));
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  iterable  $array
     * @param  string  $prepend
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = array();

        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, self::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Convert a flatten "dot" notation array into an expanded array.
     *
     * @param  iterable  $array
     * @return array
     */
    public static function undot($array)
    {
        $results = array();

        foreach ($array as $key => $value) {
            self::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param  array  $array
     * @param  array|string|int|float  $keys
     * @return array
     */
    public static function except($array, $keys)
    {
        self::forget($array, $keys);

        return $array;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     *  param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        /* if ($array instanceof Enumerable) {
            return $array->has($key);
        }

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        } */

        if (is_float($key)) {
            $key = (string) $key;
        }

        return array_key_exists($key, $array);
    }

    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  iterable  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function first($array, $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        list($class, $method) = getCallbackFromString($callback);

        foreach ($array as $key => $value) {
            if (executeCallback($class, $method, array($value, $key))) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    public static function last($array, $callback = null, $default = null)
    {
        if (is_null($callback)) {
            return empty($array) ? value($default) : end($array);
        }

        return self::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Take the first or last {$limit} items from an array.
     *
     * @param  array  $array
     * @param  int  $limit
     * @return array
     */
    public static function take($array, $limit)
    {
        if ($limit < 0) {
            return array_slice($array, $limit, abs($limit));
        }

        return array_slice($array, 0, $limit);
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  iterable  $array
     * @param  int  $depth
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
        $result = array();

        foreach ($array as $item) {
            $item = $item instanceof Collection ? $item->all() : $item;

            if (! is_array($item)) {
                $result[] = $item;
            } else {
                $values = $depth === 1
                    ? array_values($item)
                    : self::flatten($item, $depth - 1);

                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string|int|float  $keys
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (self::exists($array, $key)) {
                unset($array[$key]);

                continue;
            }

            $parts = explode('.', $key);

            // clean up before each pass
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && self::accessible($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     */
    public static function get($array, $key, $default = null)
    {
        if (! self::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (self::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return isset($array[$key]) ? $array[$key] : value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (self::accessible($array) && self::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    public static function has($array, $keys)
    {
        $keys = (array) $keys;

        if (! $array || $keys === array()) {
            return false;
        }

        foreach ($keys as $key) {
            $subKeyArray = $array;

            if (self::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (self::accessible($subKeyArray) && self::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    public static function hasAny($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (! $array) {
            return false;
        }

        if ($keys === array()) {
            return false;
        }

        foreach ($keys as $key) {
            if (self::has($array, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array  $array
     * @return bool
     */
    public static function isAssoc($array)
    {
        if (!is_array($array)) return false;
        if (array() === $array) return false;
        return array_keys($array) !== range(0, count($array) - 1);
        //return ! array_is_list($array);
    }

    /**
     * Determines if an array is a list.
     *
     * An array is a "list" if all array keys are sequential integers starting from 0 with no gaps in between.
     *
     * @param  array  $array
     * @return bool
     */
    public static function isList($array)
    {
        return !self::isAssoc($array);
        //return array_is_list($array);
    }

    /**
     * Join all items using a string. The final items can use a separate glue string.
     *
     * @param  array  $array
     * @param  string  $glue
     * @param  string  $finalGlue
     * @return string
     */
    public static function join($array, $glue, $finalGlue = '')
    {
        if ($finalGlue === '') {
            return implode($glue, $array);
        }

        if (count($array) === 0) {
            return '';
        }

        if (count($array) === 1) {
            return end($array);
        }

        $finalItem = array_pop($array);

        return implode($glue, $array) . $finalGlue . $finalItem;
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  array  $array
     * @param  array|string  $keyBy
     * @return array
     */
    public static function keyBy($array, $keyBy)
    {
        return Collection::make($array)->keyBy($keyBy)->all();
    }

    /**
     * Prepend the key names of an associative array.
     *
     * @param  array  $array
     * @param  string  $prependWith
     * @return array
     */
    public static function prependKeysWith($array, $prependWith)
    {
        /* return Collection::make($array)->mapWithKeys(function ($item, $key) use ($prependWith) {
            return array($prependWith.$key => $item);
        })->all(); */

        $result = array();

        foreach ($array as $key => $val) {
            $result[$prependWith . $key] = $val;
        }

        return $result;
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Select an array of values from an array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    public static function select($array, $keys)
    {
        $keys = self::wrap($keys);

        $result = array();

        foreach ($array as $item) {
            $res = array();
            foreach ($keys as $key) {
                if (Arr::accessible($item) && Arr::exists($item, $key)) {
                    $res[$key] = $item[$key];
                } elseif (is_object($item) && isset($item->{$key})) {
                    $res[$key] = $item->{$key};
                }
            }
            if (count($res) > 0) {
                $result[] = $res;
            }
        }

        return $result;
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  iterable  $array
     * @param  string|array|int|null  $value
     * @param  string|array|null  $key
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $results = array();

        list($value, $key) = self::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = data_get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);

                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return array($value, $key);
    }

    /**
     * Run a map over each of the items in the array.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function map($array, $callback)
    {
        $keys = array_keys($array);

        try {
            $items = array_map($callback, $array, $keys);
        } catch (Exception $e) {
            $items = array_map($callback, $array);
        }

        return array_combine($keys, $items);
    }

    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    public static function prepend($array, $value, $key = null)
    {
        if (func_num_args() == 2) {
            array_unshift($array, $value);
        } else {
            $array = array($key => $value) + $array;
        }

        return $array;
    }

    /**
     * Get a value from the array, and remove it.
     *
     * @param  array  $array
     * @param  string|int  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = self::get($array, $key, $default);

        self::forget($array, $key);

        return $value;
    }

    /**
     * Convert the array into a query string.
     *
     * @param  array  $array
     * @return string
     */
    public static function query($array)
    {
        return http_build_query($array, '', '&'/* , PHP_QUERY_RFC3986 */);
    }

    /**
     * Get one or a specified number of random values from an array.
     *
     * @param  array  $array
     * @param  int|null  $number
     * @param  bool  $preserveKeys
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function random($array, $number = null, $preserveKeys = false)
    {
        $requested = is_null($number) ? 1 : $number;

        $count = count($array);

        if ($requested > $count) {
            throw new InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }

        if (is_null($number)) {
            return $array[array_rand($array)];
        }

        if ((int) $number === 0) {
            return array();
        }

        $keys = array_rand($array, $number);

        $results = array();

        if ($preserveKeys) {
            foreach ((array) $keys as $key) {
                $results[$key] = $array[$key];
            }
        } else {
            foreach ((array) $keys as $key) {
                $results[] = $array[$key];
            }
        }

        return $results;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array  $array
     * @param  string|int|null  $key
     * @param  mixed  $value
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = array();
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Shuffle the given array and return the result.
     *
     * @param  array  $array
     * @param  int|null  $seed
     * @return array
     */
    public static function shuffle($array, $seed = null)
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    /**
     * Sort the array using the given callback or "dot" notation.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function sort($array, $key = null)
    {
        return Collection::make($array)->sortBy($key)->all();
    }

    /**
     * Sort the array in descending order using the given callback or "dot" notation.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function sortDesc($array, $callback = null)
    {
        return Collection::make($array)->sortByDesc($callback)->all();
    }

    /**
     * Recursively sort an array by keys and values.
     *
     * @param  array  $array
     * @param  int  $options
     * @param  bool  $descending
     * @return array
     */
    public static function sortRecursive($array, $options = SORT_REGULAR, $descending = false)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::sortRecursive($value, $options, $descending);
            }
        }

        if (! self::isList($array)) {
            $descending
                ? krsort($array, $options)
                : ksort($array, $options);
        } else {
            $descending
                ? rsort($array, $options)
                : sort($array, $options);
        }

        return $array;
    }

    /**
     * Conditionally compile classes from an array into a CSS class list.
     *
     * @param  array  $array
     * @return string
     */
    public static function toCssClasses($array)
    {
        $classList = self::wrap($array);

        $classes = array();

        foreach ($classList as $class => $constraint) {
            if (is_numeric($class)) {
                $classes[] = $constraint;
            } elseif ($constraint) {
                $classes[] = $class;
            }
        }

        return implode(' ', $classes);
    }

    /**
     * Filter the array using the given callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function where($array, $callback)
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    private static function callbackWhereNotNull($value)
    {
        return ! is_null($value);
    }



    /**
     * Filter items where the value is not null.
     *
     * @param  array  $array
     * @return array
     */
    public static function whereNotNull($array)
    {
        return array_filter($array, 'self::callbackWhereNotNull', ARRAY_FILTER_USE_BOTH);
    }

    /**
     * If the given value is not an array and not null, wrap it in one.
     *
     * @param  mixed  $value
     * @return array
     */
    public static function wrap($value)
    {
        if (is_null($value)) {
            return array();
        }

        return is_array($value) ? $value : array($value);
    }
}
