<?php

class Pipeline
{
    public static function send($passable)
    {
        $res = new PipelineHandler();

        return $res->send($passable);
    }
}

class PipelineHandler
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

        foreach ($this->pipes() as $pipe) {

            list($class, $params) = $this->parsePipeString($pipe);
            
            $controller = new $class;
            $params = array_merge(array($result, null), $params);

            $reflectionMethod = null;

            if (method_exists($class, $this->method)) {
                $reflectionMethod = new ReflectionMethod($class, $this->method);
            } else {
                $reflectionMethod = new ReflectionMethod($class, '__invoke');
            }

            $result = $reflectionMethod->invokeArgs($controller, $params);

            if (!($result instanceof $this->passable)) {
                return $result;
            }
        }

        if (!$destination) {
            return $result;
        }

        list($class, $method, $params) = getCallbackFromString($destination);
        $params[0] = $result;
        return call_user_func_array(array($class, $method), $params);

    }

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function thenReturn()
    {
        return $this->then(null);
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string  $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        list($name, $parameters) = explode(':', $pipe);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        } else {
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

}