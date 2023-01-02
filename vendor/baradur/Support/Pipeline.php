<?php

class Pipeline
{
    protected $container;
    protected $passable;
    protected $pipes = array();
    protected $method = 'handle';

    /**
     * Create a new class instance.
     *
     * @param  mixed|null $container
     * @return void
     */
    public function __construct($container = null)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Push additional pipes onto the pipeline.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function pipe($pipes)
    {
        $pipes = is_array($pipes) ? $pipes : func_get_args();

        array_merge($this->pipes, $pipes);

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  string  $destination
     * @return mixed
     */
    public function then($destination)
    {
        $result = $this->passable;

        foreach ($this->pipes() as $pipe)
        {
            list($class, $params) = $this->parsePipeString($pipe);
            //dump($class); // dump($params);
            
            $controller = new $class;
            $params = array_merge(array($result, null), $params);
            //$result = $controller->{$this->method}($result, null, $params);

            $reflectionMethod = new ReflectionMethod($class, $this->method);       
            $result = $reflectionMethod->invokeArgs($controller, $params);

            if (!($result instanceof $this->passable))
                return $result;
        }

        if (!$destination)
        {
            return $result;
        }

        list($class, $method, $params) = getCallbackFromString($destination);
        array_shift($params);
        return call_user_func_array(array($class, $method), array_merge(array($result), $params));

    }

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function thenReturn()
    {
        /* return $this->then(function ($passable) {
            return $passable;
        }); */

        return $this->then(null);
    }

    /**
     * Get the final piece of the Closure onion.
     *
     * @param mixed $destination
     * @return mixed
     */
    /* protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    } */

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    /* protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        // If the pipe is a callable, then we will call it directly, but otherwise we
                        // will resolve the pipes out of the dependency container and call it with
                        // the appropriate method and arguments, returning the results back out.
                        return $pipe($passable, $stack);
                    } elseif (! is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        // If the pipe is a string we will parse the string and resolve the class out
                        // of the dependency injection container. We can then build a callable and
                        // execute the pipe function giving in the parameters that are required.
                        $pipe = $this->getContainer()->make($name);

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        // If the pipe is already an object we'll just make a callable and pass it to
                        // the pipe as-is. There is no need to do any extra parsing and formatting
                        // since the object we're given was already a fully instantiated object.
                        $parameters = [$passable, $stack];
                    }

                    $carry = method_exists($pipe, $this->method)
                                    ? $pipe->{$this->method}(...$parameters)
                                    : $pipe(...$parameters);

                    return $this->handleCarry($carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    } */

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string  $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        list($name, $parameters) = explode(':', $pipe);

        if (is_string($parameters))
        {
            $parameters = explode(',', $parameters);
        }
        else
        {
            $parameters = array();
        }

        return array($name, $parameters);
    }

    /**
     * Get the array of configured pipes.
     *
     * @return array
     */
    protected function pipes()
    {
        return $this->pipes;
    }

    /**
     * Get the container instance.
     *
     * @return mixed
     */
    protected function getContainer()
    {
        if (! $this->container) {
            throw new Exception('A container instance has not been passed to the Pipeline.');
        }

        return $this->container;
    }

    /**
     * Set the container instance.
     *
     * @param mixed $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed  $passable
     * @param  \Throwable  $e
     * @return mixed
     *
     * @throws \Throwable
     */
    /* protected function handleException($passable, Throwable $e)
    {
        throw $e;
    } */
}