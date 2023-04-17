<?php

class RestCountriesAdapter implements CountriesService
{
    private $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.restcountries.endpoint');

    }

    public function getCountries()
    {
        $response = Http::withoutVerifying()->timeout(100)->get("{$this->endpoint}all");

        return $response;
        //return "ALL COUNTRIES";
    }

    public function getCountryByName($name)
    {
        $response = Http::withoutVerifying()->timeout(1)->get("{$this->endpoint}name/$name");
       
        //$response->throw();

        return $response;
        //return "COUNTRY: ".$name;
    }

    public function getCountryByCapital($capital)
    {
        $response = Http::withoutVerifying()->timeout(100)->get("{$this->endpoint}capital/$capital");

        return $response;
        //return "CAPITAL: $capital";
    }
}