<?php

Class OracleConnector
{
    protected $connection;
    public $status;

    protected $inTransaction = false;

    public function __construct($host, $user, $password, $database, $port=3306)
    {
        $this->connection = oci_pconnect($user, $password, 
            '(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = '.$host.')
            (PORT = '.$port.')) (CONNECT_DATA = (SID = '.$database.')) )');

        if (!$this->connection)
        {
            throw new Exception(oci_error());
        }

    }


    public function beginTransaction()
    {
        $this->inTransaction = true;
    }

    public function commit()
    {
        oci_commit($this->connection);
        $this->inTransaction = false;
    }
    
    public function rollBack()
    {
        oci_rollback($this->connection);
        $this->inTransaction = false;
    }

    /** @return int|bool */
    public function query($sql, $bindings=array())
    {
        return $this->execSQL($sql, $bindings, false);
    }

    public function getLastId()
    {
        return 0; // $this->connection->lastInsertId();
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
            if (env('APP_DEBUG')==1) throw new Exception($e->getMessage());

            return false;
        }
    }
    
    public function _execSQL($sql, $parent, $fill=false)
    {
        $sql = str_replace("`", "", $sql);
        //echo "SQL:".$sql."<br>";
        
        if (strpos($sql, ' LIMIT ')!==false)
        {
            preg_match('/ LIMIT[\s]*(\d*)/x', $sql, $matches);
            $sql = str_replace($matches[0], '', $sql);
            $limit = $matches[1];

            preg_match('/ OFFSET[\s]*(\d*)/x', $sql, $matches);
            $sql = str_replace($matches[0], '', $sql);
            $offset = $matches[1] ? $matches[1] : 0;

            $sql = 'SELECT T.* FROM ( SELECT T.*, rowNum as baradur_rowIndex FROM (' .
                $sql . ')T)T WHERE baradur_rowIndex > ' . $offset . ' AND baradur_rowIndex <= ' . ($limit+$offset);
        }


        //echo "SQL:".$sql."<br>";

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

        $count = 0;
        $new_bindings = array();
        while (strpos($sql, '?')!==false)
        {
            $sql = preg_replace('/\?/', ':baradur_'.$count, $sql, 1);
            $new_bindings['baradur_'.$count] = $bindings[$count];
            $count++;
        }

        $stmnt = oci_parse($this->connection, $sql);

        //dump($sql);
        //dd($new_bindings);

        foreach ($new_bindings as $key => $val)
        {
            oci_bind_by_name($stmnt, $key, $new_bindings[$key]);
        }

        if ($this->inTransaction)
        {
            if ( !oci_execute($stmnt, OCI_DEFAULT) )
            {
                oci_rollback($this->connection);
                throw new Exception(oci_error());
            }
        }
        else
        {            
            $query = oci_execute($stmnt);
        }

        //dump($query);

        //$this->error = oci_error();

        if (!$query) {
            throw new Exception(oci_error());
        }
        
        if ($fill)
        {
            while (($r = oci_fetch_object($stmnt)) != false)
            {
                if (!$parent->_toBase) {
                    $parent->_collection->put($this->objetToModel($r, $parent));
                }
                else {
                    unset($r->BARADUR_ROWINDEX);
                    $parent->_collection->put($r);
                }
    
            }
            oci_free_statement($stmnt);
    
            return $parent->_collection;
        }

        oci_free_statement($stmnt);
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

    private function arrayToObject($data)
    {
        $obj = new stdClass;
        
        foreach ($data as $key => $val)
            $obj->$key = $val;

        return $obj;
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

            $item->unsetAttribute('BARADUR_ROWINDEX');

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