<?php

class NewAccessToken
{
    public $accessToken;
    public $plainTextToken;

    public function __construct($accessToken, $plainTextToken)
    {
        $this->accessToken = $accessToken;
        $this->plainTextToken = $plainTextToken;
    }

    public function toArray()
    {
        return array(
            'accessToken' => $this->accessToken,
            'plainTextToken' => $this->plainTextToken,
        );
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}