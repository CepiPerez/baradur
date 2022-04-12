<?php

Class CollectionItem
{
    protected static $_parent = null;
    protected static $_query = null;

    public function __construct($parent, $query)
    {
        self::$_parent = $parent;
        self::$_query = $query;
    }

    /**
     * Saves the current record in database\
     * Uses INSERT for new record\
     * Uses UPDATE for retrieved record\
     * Returns error or empty string if ok
     * 
     * @return string
     */
    public function save()
    {
        //$res = self::getInstance();
        return self::$_query->save($this);
    }

    /**
     * Updates the record in database\
     * Returns error or empty string if ok
     * 
     * @param array $values
     * @return string
     */
    public static function update($values)
    {
        //var_dump(self::getInstance()->getQuery());
        return self::$_query->update($values);
    }


}
 