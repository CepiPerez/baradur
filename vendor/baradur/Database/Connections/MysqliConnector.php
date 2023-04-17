<?php

Class MysqliConnector extends Connector
{
    protected $connection;
    public $status;
    protected $inTransaction = false;

    public function __construct($host, $user, $password, $database, $port=3306)
    {
        $this->database = $database;

        $this->connection = new mysqli($host, $user, $password, $database, $port);
        $this->connection->set_charset("utf8");
        mysqli_query($this->connection, "SET NAMES 'utf8'");
        //mysqli_report(MYSQLI_REPORT_OFF);
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        if (!$this->connection) {
            throw new Exception("Error trying to connect to database");
        }
    }

    public function isInTransaction()
    {
        return $this->inTransaction;
    }
    
    public function beginTransaction()
    {
        $this->inTransaction = true;
        $this->connection->query('START TRANSACTION');
        //throw new Exception('Transactions not supported');
    }

    public function commit()
    {
        $this->inTransaction = true;
        $this->connection->query('COMMIT');
        //throw new Exception('Transactions not supported');
    }
    
    public function rollBack()
    {
        $this->inTransaction = true;
        $this->connection->query('ROLLBACK');
        //throw new Exception('Transactions not supported');
    }

    public function getLastId()
    {
        return $this->lastId;
    }

    public function _execUnpreparedSQL($sql)
    {
        if (config('app.debug_info')) {
            global $debuginfo;
            
            $debuginfo['queryes'][] = $sql; // preg_replace('/\s\s+/', ' ', str_replace("'", "\"", $sql));
        }

        $this->connection->query($sql);

        $this->lastId = $this->connection->insert_id;

        $this->status = $this->connection->affected_rows;

        return true;
    }

    public function _execSQL($sql, $parent, $fill=false)
    {    
        $types = '';
        $params = array();
        $bindings = $parent instanceof Builder? $parent->_bindings : $parent;

        $bindings = Builder::__joinBindings($bindings);
        
        foreach($bindings as $val) {
            if (is_integer($val)) {
                $types .= 'i';
            } elseif (is_float($val)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $params[] = $val;
        }

        if (config('app.debug_info'))
        {
            global $debuginfo;

            $result = Builder::__getPlainSqlQuery($sql, $bindings);

            $debuginfo['queryes'][] = $result; // preg_replace('/\s\s+/', ' ', str_replace("'", "\"", $sql));
        }

        $query = $this->connection->prepare($sql);

        if (count($params) > 0) {
            $params = array_merge(array($types), $params);
            call_user_func_array(array($query, 'bind_param'), $this->refValues($params));
        }

        //echo "SQL NUEVO:".$sql."<br>".implode(', ', $params)."<br>";

        $query->execute();
        
        $meta = $query->result_metadata();

        $this->status = $query->affected_rows;

        $this->lastId = $this->connection->insert_id;

        if (!$meta) return true;
    
        $row = array();

        while ( $field = $meta->fetch_field() ) {
            $parameters[] = &$row[$field->name];
        } 

        call_user_func_array(array($query, 'bind_result'), $this->refValues($parameters));

        if ($fill) {
            while( $query->fetch() ) {
                $r = new stdClass; 

                foreach( $row as $key => $val ) { 
                    $r->$key = is_null($val)? null : (string)$val; 
                }

                if (!$parent->_toBase) {
                    $parent->_collection->put($this->objetToModel($r, $parent));
                } else {
                    $parent->_collection->put($r);
                }

            }
            return $parent->_collection;
        }

        return true;
    }

    public function refValues($arr)
    {
        if (count($arr)==0) return array();

        if (strnatcmp(phpversion(),'5.3') >= 0) {
            $refs = array();

            foreach($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }

            return $refs;
        }
        
        return $arr;
    }


    public function getRowSet($sql, $bindings=array())
    {
        $bindings = is_array($bindings) ? $bindings : array($bindings);

        foreach ($bindings as $val) {
            if (is_string($val)) {
                $val = "'$val'";
            }
            $sql = preg_replace('/\?/', $val, $sql, 1);
        }

        if ($this->connection->multi_query($sql)) {
            do {
                $child = array();

                if ($result = $this->connection->store_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $child[] = (object)$row;
                    }
                    $result->free();
                    $sets[] = $child;
                }

            }
            while ($this->connection->next_result());
        }

        return $sets;
    }

}