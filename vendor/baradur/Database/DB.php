<?php

class DB
{

    /**
     * Assigns the table to DB Class\
     * Optionally you can assing primary key 
     * using table('table_name:primary_key')\
     * Returns a Query builder
     * 
     * @param string $table
     * @return Builder
     */
    public static function table($table, $as = null)
    {
        $res = Model::instance('Model', $table, $as);
        return $res; //->getQuery();
    }

    public static function affectingStatement($query, $bindings=array())
    {
        $res = Model::instance('DB', 'dummy');
        $res->_bindings = $bindings;
        $connector = $res->toBase()->connector();
        
        $stmnt = self::statement($query, $bindings);

        if (!$stmnt) {
            return false;
        }

        return $connector->status;
    }

    public static function statement($query, $bindings=array())
    {
        $res = Model::instance('DB', 'dummy');
        $res->_bindings = $bindings;
        return $res->toBase()->connector()->execSQL($query, $res);
    }

    public static function insert($query, $bindings=array())
    {
        return self::statement($query, $bindings);
    }

    public static function update($query, $bindings=array())
    {
        return self::affectingStatement($query, $bindings);
    }

    public static function delete($query, $bindings=array())
    {
        return self::affectingStatement($query, $bindings);
    }

    public static function unprepared($query)
    {
        $res = Model::instance('DB', 'dummy');
        return $res->toBase()->connector()->execUnpreparedSQL($query);
    }

    public static function select($query, $bindings=array())
    {
        if ($query instanceof Expression) {
            $query = $query->__toString();
        }

        $res = Model::instance('DB', 'dummy');
        $res->_bindings = $bindings;
        $res->toBase()->connector()->execSQL($query, $res, true);
        return $res->_collection->all();
    }

    public static function raw($value)
    {
        return new Expression($value);
    }

    public static function query()
    {
        return Model::instance('DB', 'dummy')->toBase();
    }

    public static function beginTransaction()
    {
        return Model::instance('DB', 'dummy')->connector()->beginTransaction();
    }

    public static function commit()
    {
        return Model::instance('DB', 'dummy')->connector()->commit();
    }

    public static function rollBack()
    {
        return Model::instance('DB', 'dummy')->connector()->rollBack();
    }

    public static function transaction($closure)
    {
        list($class, $method, $params) = getCallbackFromString($closure);
        
        try {
            self::beginTransaction();

            #call_user_func_array(array($class, $method), $params);
            executeCallback($class, $method, $params);

            self::commit();

            return true;
        }
        catch(Exception $e) {
            self::rollBack();

            return false;
        }
    }

    public static function selectResultSets($query, $bindings = array())
    {
        $res = Model::instance('DB', 'dummy');

        try {
            return $res->toBase()->connector()->getRowSet($query, $bindings);
        }
        catch (Exception $e) {

            if (config('app.debug')) {
                throw new QueryException($e->getMessage());
            }

            return false;
        }
    }


    public function getConnectionName() { return null; }
    public function getKeyName() { return 'dummy'; }
    public function getFillable() { return array(); }
    public function getGuarded() { return array(); }
    public function getHidden() { return array(); }
    public function getAppends() { return array(); }
    public function getRouteKeyName() { return 'dummy'; }
    public function usesSoftDeletes() { return false; }
    public function __getWith() { return array(); }
    public function __getGlobalScopes() { return array(); }
}

