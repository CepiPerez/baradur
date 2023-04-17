<?php

interface CountriesService
{
    public function getCountries();
    public function getCountryByName($name);
    public function getCountryByCapital($capital);
}

