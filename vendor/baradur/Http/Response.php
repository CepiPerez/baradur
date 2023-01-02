<?php

class Response
{
    protected $response;
    protected $decoded;
    public $cookies;
    public $transferStats;

    public function __construct($response)
    {
        $this->response = $response;
    }

    public function body()
    {
        return (string) $this->response->getBody();
    }

    public function json($key = null, $default = null)
    {
        if (! $this->decoded) {
            $this->decoded = json_decode($this->body(), true);
        }

        if (is_null($key)) {
            return $this->decoded;
        }

        if (isset($this->decoded[$key])) {
            return $this->decoded[$key];
        }

        return $default;
    }

    public function object()
    {
        return json_decode($this->body(), false);
    }

    public function collect($key = null)
    {
        return collect($this->json($key));
    }

    public function header($header)
    {
        return $this->response->getHeaderLine($header);
    }

    public function headers()
    {
        return $this->response->getHeaders();
    }

    public function status()
    {
        return (int) $this->response->code;
    }

    public function reason()
    {
        return $this->response->getReasonPhrase();;
    }

    public function effectiveUri()
    {
        return $this->info['url'];
    }

    public function successful()
    {
        return $this->status() >= 200 && $this->status() < 300;
    }

    public function ok()
    {
        return $this->status() === 200;
    }

    public function redirect()
    {
        return $this->status() >= 300 && $this->status() < 400;
    }

    public function unauthorized()
    {
        return $this->status() === 401;
    }

    public function forbidden()
    {
        return $this->status() === 403;
    }

    public function failed()
    {
        return $this->error_code || $this->error_string;
    }

    public function clientError()
    {
        return $this->status() >= 400 && $this->status() < 500;
    }

    public function serverError()
    {
        return $this->status() >= 500;
    }

    /* public function onError(callable $callback)
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    } */

    public function cookies()
    {
        return $this->cookies;
    }

    /* public function handlerStats()
    {
        return $this->transferStats?->getHandlerStats() ?? [];
    } */

    /* public function close()
    {
        $this->response->getBody()->close();

        return $this;
    } */

    public function toPsrResponse()
    {
        return $this->response->getRawResponse();
    }

    /* public function toException()
    {
        if ($this->failed()) {
            return new RequestException($this);
        }
    } */

    /* public function throw()
    {
        $callback = func_get_args()[0] ?? null;

        if ($this->failed()) {
            throw tap($this->toException(), function ($exception) use ($callback) {
                if ($callback && is_callable($callback)) {
                    $callback($this, $exception);
                }
            });
        }

        return $this;
    } */

    /* public function throwIf($condition)
    {
        return $condition ? $this->throw() : $this;
    } */

    public function offsetExists($offset)
    {
        $json = $this->json();
        return isset($json[$offset]);
    }

    public function offsetGet($offset)
    {
        $json = $this->json();
        return $json[$offset];
    }

    /* public function offsetSet($offset, $value): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    } */

    /* public function offsetUnset($offset): void
    {
        throw new LogicException('Response data may not be mutated using array access.');
    } */

    public function __toString()
    {
        return $this->body();
    }

    /* public function __call($method, $parameters)
    {
        return static::hasMacro($method)
                    ? $this->macroCall($method, $parameters)
                    : $this->response->{$method}(...$parameters);
    } */
}