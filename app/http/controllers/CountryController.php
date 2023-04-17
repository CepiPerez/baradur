<?php

class CountryController
{

    public function __construct(private CountriesService $restCountriesAdapter)
    {

    }

    public function getCountries()
    {
        return $this->restCountriesAdapter->getCountries();
    }

    public function getCountryByName()
    {
        $name = request()->get('name');

        return $this->restCountriesAdapter->getCountryByName($name);
    }

    public function getCountryByCapital()
    {
        $capital = request()->get('capital');

        return $this->restCountriesAdapter->getCountryByCapital($capital);
    }


}