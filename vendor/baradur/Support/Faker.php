<?php

class Faker
{
    public static function resetUnique() {
        FakerUnique::reset();
    }

    public static function setCounter($num) {
        FakerUnique::setCounter($num);
    }

    public function unique() {
        return FakerUnique::getInstance();
    }

    public function phoneNumber() { return FakerPhoneNumber::phoneNumber(); }
    public function safePhoneNumber() { return FakerPhoneNumber::safePhoneNumber(); }

    public function name() { return FakerName::name(); }
    public function firstName() { return FakerName::firstName(); }
    public function lastName() { return FakerName::lastName(); }
    public function prefix() { return FakerName::prefix(); }
    public function suffix() { return FakerName::suffix(); }

    public function word() { return FakerLorem::word(); }
    public function words($num = 3) { return FakerLorem::word($num); }
    public function sentence($num = 3) { return FakerLorem::sentence($num); }
    public function sentences($num = 3) { return FakerLorem::sentences($num); }
    public function paragraph($num = 3) { return FakerLorem::paragraph($num); }
    public function paragraphs($num = 3) { return FakerLorem::paragraphs($num); }

    public function email($name = null) { return FakerInternet::email($name); }
    public function freeEmail($name = null) { return FakerInternet::freeEmail($name); }
    public function safeEmail($name = null) { return FakerInternet::safeEmail($name); }
    public function userName($name = null) { return FakerInternet::userName($name); }
    public function domainName() { return FakerInternet::domainName(); }
    public function safeDomainName() { return FakerInternet::safeDomainName(); }
    public function domainWord() { return FakerInternet::domainWord(); }
    public function domainSuffix() { return FakerInternet::domainSuffix(); }
    public function ipv4Address() { return FakerInternet::ipv4Address(); }
    public function slug($str = null, $glue = array('.', '-', '_')) { return FakerInternet::slug($str, $glue); }

    public function timestamp() { return FakerDateTime::timestamp(); }
    public function date($format = null) { return FakerDateTime::date($format); }
    public function dateFormat() { return FakerDateTime::dateFormat(); }
    public function time($format = null) { return FakerDateTime::time($format); }
    public function timeFormat() { return FakerDateTime::timeFormat(); }
    public function dateTime($format = null) { return FakerDateTime::dateTime($format); }
    public function dateTimeFormat() { return FakerDateTime::dateTimeFormat(); }
    public function month() { return FakerDateTime::month(); }
    public function monthAbbr() { return FakerDateTime::monthAbbr(); }
    public function weekday() { return FakerDateTime::weekday(); }
    public function weekdayAbbr() { return FakerDateTime::weekdayAbbr(); }

    public function company_name() { return FakerCompany::name(); }
    public function company_suffix() { return FakerCompany::suffix(); }
    public function company_catchPhrase() { return FakerCompany::catchPhrase(); }
    public function company_bs() { return FakerCompany::bs(); }
    
    public function address() { return FakerAddress::address(); }
    public function streetName() { return FakerAddress::streetName(); }
    public function streetAddress($includeSecondary = false) { return FakerAddress::streetAddress($includeSecondary); }
    public function secondaryAddress() { return FakerAddress::secondaryAddress(); }
    public function city() { return FakerAddress::city(); }
    public function state() { return FakerAddress::state(); }
    public function stateAbbr() { return FakerAddress::stateAbbr(); }
    public function zipCode() { return FakerAddress::zipCode(); }
    public function zip() { return FakerAddress::zip(); }
    public function postcode() { return FakerAddress::postcode(); }
    public function cityStateZip() { return FakerAddress::cityStateZip(); }
    public function country() { return FakerAddress::country(); }

    public function product() { return FakerCommerce::product(); }
    public function productName() { return FakerCommerce::productName(); }
    public function productAdjective() { return FakerCommerce::productAdjective(); }
    public function productMaterial() { return FakerCommerce::productMaterial(); }
    public function productDescription() { return FakerCommerce::productDescription(); }
    public function productDepartment() { return FakerCommerce::department(); }
    public function productColor() { return FakerCommerce::color(); }
    public function productPrice() { return FakerCommerce::price(); }

    public function randomElement($elements = array())
    {
        return FakerBase::pickOne($elements);
    }


}