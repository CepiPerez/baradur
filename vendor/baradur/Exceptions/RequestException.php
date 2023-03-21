<?php

class RequestException extends Exception
{
    public $response;

    public function __construct(Response $response)
    {
        parent::__construct($this->prepareMessage($response), $response->status());

        $this->response = $response;
    }

    protected function prepareMessage(Response $response)
    {
        $message = "HTTP request returned status code {" . $response->status() . "}";

        $summary = $response->toPsrResponse();

        return $message; //is_null($summary) ? $message : $message .= ":\n{"  .$summary . "}\n";
    }
}