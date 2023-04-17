<?php

/**
 * @method string phoneNumber()
 * @method string safePhoneNumber()
 * @method string name()
 * @method string firstName()
 * @method string lastName()
 * @method string prefix()
 * @method string suffix()
 * @method string word()
 * @method string words($num = 3)
 * @method string sentence($num = 3)
 * @method string sentences($num = 3)
 * @method string paragraph($num = 3)
 * @method string paragraphs($num = 3)
 * @method string email($name = null)
 * @method string freeEmail($name = null)
 * @method string safeEmail($name = null)
 * @method string userName($name = null)
 * @method string domainName()
 * @method string safeDomainName()
 * @method string domainWord()
 * @method string domainSuffix()
 * @method string ipv4Address()
 * @method string slug($str = null, $glue = array('.', '-', '_'))
 * @method string timestamp()
 * @method string date($format = null)
 * @method string dateFormat()
 * @method string time($format = null)
 * @method string timeFormat()
 * @method string dateTime($format = null)
 * @method string dateTimeFormat()
 * @method string month()
 * @method string monthAbbr()
 * @method string weekday()
 * @method string weekdayAbbr()
 * @method string company_name()
 * @method string company_suffix()
 * @method string company_catchPhrase()
 * @method string company_bs()
 * @method string address()
 * @method string streetName()
 * @method string streetAddress($includeSecondary = false)
 * @method string secondaryAddress()
 * @method string city()
 * @method string state()
 * @method string stateAbbr()
 * @method string zipCode()
 * @method string zip()
 * @method string postcode()
 * @method string cityStateZip()
 * @method string country()
 * @method string product()
 * @method string productName()
 * @method string productAdjective()
 * @method string productMaterial()
 * @method string productDescription()
 * @method string productDepartment()
 * @method string productColor()
 * @method string productPrice()
 * @method string randomElement($elements = array())
 */

Class FakerUnique 
{
    private $faker;
    private $uniques = array();

    private static $counter;

    private static $instance;

    public function __construct()
    {
        $this->faker = new Faker;
    }

    public function __call($name, $arguments)
    {
        $result = $this->faker->$name();
        
        if (isset($this->uniques[$name])) {

            $count = 1;

            while (in_array($result, $this->uniques[$name])) {
                $result = $this->faker->$name();
                ++$count;
                if ($count >= self::$counter) {
                    throw new RuntimeException("Faker unique error: items quanqtity is greater than existent elements");
                }
            }
        }

        $this->uniques[$name][] = $result;
        return $result;
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FakerUnique();
        }

        return self::$instance;
    }

    public static function reset()
    {
        self::$instance = null;
    }

    public static function setCounter($num)
    {
        self::$counter = $num;
    }

}