<?php

class Dispatcher
{
    protected $listeners = array();

    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]);
    }

    public function until($event, $payload = array())
    {
        return $this->dispatch($event, $payload, true);
    }

    private function setListeners($event)
    {
        global $listeners;
        $this->listeners = $listeners[get_class($event)];
    }

    public function dispatch($event, $payload = array(), $halt = false)
    {
        $this->setListeners($event);

        $responses = array();
        
        foreach ($this->listeners as $listener) {
            
            $method = 'handle'; 

            if (is_array($listener) && !is_closure($listener)) {
                $listener = $listener[0];
                $method = $listener[1];
            }


            if (is_closure($listener)) {
                list($c, $m) = getCallbackFromString($listener);
                $response = executeCallback($c, $m, array($event));
            }
            else {
                $listener = new $listener;                
                $response = $listener->handle($event);
            }

            if ($halt && !is_null($response)) {
                return $response;
            }

            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    private function executeListener($listener, $event)
    {
        global $_class_list;

        if (is_array($listener) && !is_closure($listener)) {
            $class = $listener[0];
            $method = $listener[1];
            $class = new $class;
            return $class->{$method}($event);
        }

        if (is_closure(($listener))) {
            list($c, $m) = getCallbackFromString($event);
            return executeCallback($c, $m, array($event));
        }

        if (isset($_class_list[$listener])) {
            return $listener->handle($event);
        }

        return null;
    }

    protected function parseEventAndPayload($event, $payload)
    {
        if (is_object($event)) {
            list($payload, $event) = array(array($event), get_class($event));
        }

        return array($event, Arr::wrap($payload));
    }

    public function forget($event)
    {
        unset($this->listeners[$event]);
    }

    public function forgetPushed()
    {
        foreach ($this->listeners as $key => $value) {
            if (str_ends_with($key, '_pushed')) {
                $this->forget($key);
            }
        }
    }

    public function getRawListeners()
    {
        return $this->listeners;
    }
}