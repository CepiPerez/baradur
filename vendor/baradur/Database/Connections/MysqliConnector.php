<?php

Class MysqliConnector
{
    protected $connection;
    public $status;

    public function __construct($host, $user, $password, $database, $port=3306)
    {
        $this->connection = new mysqli($host, $user, $password, $database, $port);
        $this->connection->set_charset("utf8");
        mysqli_query($this->connection, "SET NAMES 'utf8'");
        //mysqli_report(MYSQLI_REPORT_OFF);
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        if (!$this->connection) {
            throw new Exception("Error trying to connect to database");
        }
    }


    public function beginTransaction()
    {
        throw new Exception('Transactions not supported');
    }

    public function commit()
    {
        throw new Exception('Transactions not supported');
    }
    
    public function rollBack()
    {
        throw new Exception('Transactions not supported');
    }

    /** @return int|bool */
    public function query($sql, $bindings=array())
    {
        return $this->execSQL($sql, $bindings, false);
    }

    public function getLastId()
    {
        return $this->status->insert_id;
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
        //global $version;
        //var_dump($wherevals);
        
        /* foreach ($wherevals as $val)
        {
            foreach ($val as $k => $v)
            {
                //var_dump($v);
                $sql = preg_replace('/\?/', "'".$v."'", $sql, 1);
            }
        }
        $res = array(); */

        //$sql = str_replace("'NOW()'", "NOW()", $sql);
        
        //echo "SQL:".$sql."<br>";

        //$query = $this->connection->query($sql);

        $query = $this->connection->prepare($sql);
    
        $types = '';
        $params = array();
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

        foreach($bindings as $val)
        {
            if (is_integer($val)) {
                $types .= 'i';
            }
            elseif (is_float($val)) {
                $types .= 'd';
            }
            else {
                $types .= 's';
            }
            $params[] = $val;
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


        //dump( implode(',',  array_merge(array($types), $params)) );
        if (count($params) > 0) {
            //call_user_func_array(array($query, 'bind_param'), array_merge(array($types), $params));
            $params = array_merge(array($types), $params);
            call_user_func_array(array($query, 'bind_param'), $this->refValues($params));
        }

        //echo "SQL NUEVO:".$sql."<br>".implode(', ', $params)."<br>";

        $query->execute();
        
        $meta = $query->result_metadata();

        //$this->error = $this->connection->error;


        $this->status = $query;

        if (!$meta) return true;
    
        $row = array();
        while ( $field = $meta->fetch_field() ) {
            $parameters[] = &$row[$field->name];
        } 

        call_user_func_array(array($query, 'bind_result'), $this->refValues($parameters));

        /* if (!$query) {
            throw new Exception($this->error);
        } */

    
        if ($fill)
        {
            while( $query->fetch() )
            {
                $r = new stdClass; 
                foreach( $row as $key => $val ) { 
                    $r->$key = is_null($val)? null : (string)$val; 
                }

                if (!$parent->_toBase)
                    $parent->_collection->put($this->objetToModel($r, $parent));
                else
                    $parent->_collection->put($r);

            }
            return $parent->_collection;
        }

        return true;

            
        /* echo "SQL NUEVO:".$sql."<br>";
        //var_dump($wherevals);


        $stmt = $this->connection->prepare($sql) or die (
            "Prepare failed: (" . $this->connection->errno . ") " . $this->connection->error
        );

        $bindtypes = '';
        $bindvalues = array();
        $count = 0;
        foreach ($wherevals as $val)
        {
            foreach ($val as $k => $v) {
                $bindtypes .= $k;
                $bindvalues['val'.$count] = $v;
                ++$count;
            }
        }
        if (count($bindvalues)>0) 
            array_unshift($bindvalues, $bindtypes);

        var_dump($bindvalues);

        if (count($bindvalues)>0)
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($bindvalues));
       
        $stmt->execute();

        $this->error = $stmt->error;
       
        $meta = $stmt->result_metadata();

        if (!$meta) return;
    
        while ( $field = $meta->fetch_field() ) {
            $parameters[] = &$row[$field->name];
        } 

        call_user_func_array(array($stmt, 'bind_result'), $this->refValues($parameters));
        
        while ( $stmt->fetch() ) {

            if (!$collection)
                $collection = new Collection('stdClass');

            $x = array(); 
            foreach( $row as $key => $val ) { 
                $x[$key] = $val; 
            } 
            $collection->put($x); 
        } 


        //if (count($results)==0) return array();
       
        return $collection; */
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