<?php

class Command
{
    protected $signature;
    protected $description;
    protected $callback;

    protected $arguments = array();
    protected $options = array();

    public $output;

    public function __construct()
    {
        $this->output = new CommandOutput;
    }

    public function signature()
    {
        return reset(explode(' ', $this->signature));
    }

    public function description()
    {
        return $this->description;
    }

    public function info($message)
    {
        Artisan::info($message);
    }

    public function error($message)
    {
        Artisan::error($message);
    }

    public function line($message)
    {
        printf($message . "\n");
    }

    public function table($headers, $items)
    {
        $len = array();

        $values = array();

        foreach ($items as $item) {
            $values[] = is_array($item) ? array_values($item) : array($item);
        }

        for ($i=0; $i < count($headers); $i++) {

            if (!isset($len[$i])) {
                $len[$i] = strlen($headers[$i])+2;
            } elseif ($len[$i] < strlen($headers[$i])) {
                $len[$i] = strlen($headers[$i])+2;
            }

            for ($j=0; $j < count($headers); $j++) {
                if ($len[$i] < strlen($values[$i][$j])) {
                    $len[$i] = strlen($values[$i][$j])+2;
                }
            }
        }

        /* printf(' +');
        foreach ($len as $l) {
            printf(str_repeat('-', $l + 4) . '+');
        }
        printf("\n"); */

        /* printf(' |'); */
        for ($i=0; $i < count($headers); $i++) {
            $h = "\033[32m".$headers[$i]."\033[m";
            $str = '  ' . str_pad($h, $len[$i]+9, ' ') . '   '; //'  |';
            printf($str);
        }
        printf("\n");

        /* printf(' |');
        foreach ($len as $l) {
            printf(str_repeat('-', $l + 4) . '+');
        }
        printf("\n"); */

        for ($i=0; $i < count($values); $i++) {
            /* printf(' |'); */
            for ($j=0; $j < count($headers); $j++) {                
                $str = '  ' . str_pad($values[$i][$j], $len[$j]+2, ' ') . '  '; // . ' |';
                printf($str);
            }
            printf("\n");
        }

        /* printf(' +');
        foreach ($len as $l) {
            printf(str_repeat('-', $l + 4) . '+');
        } */
        /* printf("\n"); */
        printf("\n");
    }

    private function stripName($string)
    {
        $string = ltrim(rtrim($string, '}'), '{');
        return ltrim($string, '--');
    }

    public function setParameters($parameters)
    {
        $names = explode(' ', $this->signature);
        array_shift($names);

        for ($i=0; $i < count($names); $i++) {

            $key = $this->stripName($names[$i]);

            if (strpos($names[$i], '--')===false) {
                $this->arguments[$key] = $parameters[$i];
            } else {
                $param = end(explode('=', $parameters[$i]));
                $this->options[$key] = $param;
            }
        }
    }

    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setArgument($key, $value)
    {
        $this->arguments[$key] = $value;
    }

    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    public function purpose($description)
    {
        $this->description = $description;
    }

    public function arguments()
    {
        return $this->arguments;
    }

    public function callback()
    {
        return $this->callback;
    }

    public function argument($key)
    {
        return isset($this->arguments[$key]) ? $this->arguments[$key] : null;
    }

    public function options()
    {
        return $this->options;
    }

    public function option($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    public function ask($question)
    {
        return Artisan::input($question);
    }

    public function secret($question)
    {
        return Artisan::input($question, true);
    }

    public function newLine($count = 1)
    {
        printf(str_repeat("\n", $count));
    }

    public function choice($question, $values, $defaultIndex = null)
    {
        return Artisan::choice($question, $values, $defaultIndex);
    }

    public function withProgressBar($collection, $callback)
    {
        list($class, $method, $params) = getCallbackFromString($callback);

        if ($collection instanceof Collection) {
            $collection = $collection->all();
        }

        $bar = $this->output->createProgressBar(count($collection));

        $bar->start();

        foreach ($collection as $item) {
            executeCallback($class, $method, array($item), $this);
            $bar->advance();
        }

        $bar->finish();
    }

}