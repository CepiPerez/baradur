<?php

class Benchmark
{
    /**
     * Measure a callable or array of callables over the given number of iterations.
     *
     * @return array|float
     */
    public static function measure($benchmarkables, $iterations = 1)
    {
        $benchmarkables = is_array($benchmarkables) ? $benchmarkables : array($benchmarkables);

        $result = array();

        foreach($benchmarkables as $key => $value) {
            $start = microtime(true);

            for ($i=0; $i < $iterations; $i++) {
                list($class, $method) = getCallbackFromString($value);
                executeCallback($class, $method, array());
            }

            $end = microtime(true) - $start;

            if ($end > 1) {
                $result[$key] = number_format($end, 3) . "s";
            } else {
                $result[$key] = number_format($end *1000, 3) . "ms";
            }
        }
        
        return count($result)==1? $result[0] : $result;
        
    }

    /**
     * Measure a callable or array of callables over the given number of iterations, then dump and die.
     *
     * @return never
     */
    public static function dd($benchmarkables, $iterations = 1)
    {
        $result = self::measure($benchmarkables, $iterations);

        dd($result);
    }
}