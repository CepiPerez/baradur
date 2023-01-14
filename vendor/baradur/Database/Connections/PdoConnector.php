<?php

Class PdoConnector
{
    protected $connection;
    public $status;

    protected $inTransaction = false;

    public function __construct($host, $user, $password, $database, $port=3306)
    {
        try
        {
            $this->connection = new PDO("mysql:host=$host; dbname=$database", $user, $password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e)
        {
            throw new Exception($e->getMessage());
        }

    }

    public function beginTransaction()
    {
        $this->inTransaction = true;
        $this->connection->beginTransaction();
    }

    public function commit()
    {
        $this->connection->commit();
        $this->inTransaction = false;
    }
    
    public function rollBack()
    {
        $this->connection->rollBack();
        $this->inTransaction = false;
    }


    /** @return int|bool */
    public function query($sql, $bindings=array())
    {
        return $this->execSQL($sql, $bindings, false);
    }

    public function getLastId()
    {
        return $this->connection->lastInsertId();
    }

    public function execSQL($sql, $parent, $fill=false)
    {
        //var_dump($wherevals);
        if ($fill && $parent->_collection==null)
            $parent->_collection = new Collection('stdClass');
        
        try
        {
            return $this->_execSQL($sql, $parent, $fill);
        }
        catch (Exception $e) 
        {
            if (env('APP_DEBUG')==1) throw new Exception($e->getMessage(), 100);

            return false;
        }
    }
    
    public function _execSQL($sql, $parent, $fill=false)
    {

        $query = $this->connection->prepare($sql);
    
        $bindings = $parent instanceof Builder? $parent->_bindings : $parent;


        if (is_assoc($bindings))
        {
            $bind = array();
            foreach ($bindings['select'] as $b) $bind[] = $b;
            foreach ($bindings['join'] as $b) $bind[] = $b;
            foreach ($bindings['where'] as $b) $bind[] = $b;
            foreach ($bindings['union'] as $b) $bind[] = $b;
            foreach ($bindings['having'] as $b) $bind[] = $b;

            $bindings = $bind;
        }

        if (env('DEBUG_INFO')==1)
        {
            global $debuginfo;

            $result = $sql;

            foreach ($bindings as $val)
            {
                if (is_string($val)) $val = "'$val'";
                $result = preg_replace('/\?/', $val, $result, 1);
            }

            $debuginfo['queryes'][] = $result; // preg_replace('/\s\s+/', ' ', str_replace("'", "\"", $sql));
        }

        $query->execute($bindings);

        
        /* $error = $query->errorInfo();
        if (!$query) {
            throw new Exception($this->error[2]);
        } */
        
        if ($fill)
        {
            while( $r = $query->fetchObject() )
            {
                if (!$parent->_toBase)
                    $parent->_collection->put($this->objetToModel($r, $parent));
                else
                    $parent->_collection->put($r);

            }

            return $parent->_collection;
        }

        return $query;

            
    }

    public function refValues($arr)
    {
        if (count($arr)==0) return array();

        if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach($arr as $key => $value)
                $refs[$key] = &$arr[$key];
            return $refs;
        }
        return $arr;
    }

    private function objetToModel($data, $builder)
    {
        $class = $builder->_parent;
        $item = new $class;

        /* foreach ($data as $key => $val)
        {
            $item->$key = $val;

            foreach ($builder->_appends as $append)
            {
                $item->setAppendAttribute($append, $item->$append);
            }
        } */

        //$this->__new = false;
        $item->_setOriginalRelations($builder->_eagerLoad);

        unset($item->_global_scopes);
        unset($item->timestamps);

        $item->setAttributes(CastHelper::processCasts(
            (array)$data,
            $builder->_model,
            false
        ));

        /* foreach ($builder->_model->getAppends() as $append)
        {
            $item->setAppendAttribute($append, $item->$append);
        } */

        foreach ($item->getAttributes() as $key => $val)
        {
            if ($builder->_softDelete)
            {
                $item->unsetAttribute('deleted_at');
            }


            $camel = Helpers::snakeCaseToCamelCase($key);

            if (method_exists($builder->_parent, 'get'.ucfirst($camel).'Attribute'))
            {
                $fn = 'get'.ucfirst($camel).'Attribute';
                $val = $item->$fn($val);
            }
            if (method_exists($builder->_parent, $camel.'Attribute'))
            {
                $fn = $camel.'Attribute';
                $nval = $item->$fn($val, (array)$item);
                if (isset($nval['get'])) $item->$key = $nval['get'];
            }
        }

        foreach ($item->getAttributes() as $key => $val)
        {
            $item->_setOriginalKey($key, $val);
        }

        return $item;

    }
}