<?php

class DB extends Model
{
    # DB Class should be used with table() at first!

    # Since it's a global table class, find() command
    # will fail if primary key is not 'id'
    # However, wen can assign the primary key in table()
    # using table('products:code')
    
    /**
     * Assigns the table to DB Class\
     * Optionally you can assing primary key 
     * using table('table_name:primary_key')\
     * Returns a Query builder
     * 
     * @param string $table
     * @return QueryBuilder
     */
    public static function table($table)
    {
        //self::initialize('DB');
        $res = self::getInstance($table);
        return $res->getQuery();
    }

    public static function statement($query)
    {
        $res = self::getInstance('DB');
        return $res->getQuery()->query($query);
        /* return $res; */
    }


}

