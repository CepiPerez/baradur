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
     * @return Builder
     */
    public static function table($table)
    {
        $res = parent::instance('DB', $table);
        return $res; //->getQuery();
    }

    public static function statement($query)
    {
        $res = parent::instance('DB');
        return $res->runQuery($query);
        //return $res->getQuery()->query($query);
    }

    public static function select($query, $bindings=array())
    {
        if ($query instanceof Raw) {
            $query = $query->query;
        }

        $res = parent::instance('DB');
        $res->_bindings = $bindings;
        $res->toBase()->connector()->execSQL($query, $res, true);
        return $res->_collection;
    }

    public static function raw($query, $bindings=array())
    {
        return new Raw($query, $bindings);
    }

    /**
     * Executes the SQL $query
     * 
     * @param string $query
     * @return Collection
     */
    public static function query($query)
    {
        $res = parent::instance('DB');
        return $res->runQuery($query);
    }

    public static function beginTransaction()
    {
        return parent::instance('DB')->connector()->beginTransaction();
    }

    public static function commit()
    {
        return parent::instance('DB')->connector()->commit();
    }

    public static function rollBack()
    {
        return parent::instance('DB')->connector()->rollBack();
    }

    public static function transaction($closure)
    {
        list($class, $method, $params) = getCallbackFromString($closure);
        
        try
        {
            self::beginTransaction();

            call_user_func_array(array($class, $method), $params);

            self::commit();

            return true;
        }
        catch(Exception $e)
        {
            self::rollBack();

            return false;
        }
    }

}

