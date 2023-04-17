<?php

Class OracleConnector extends Connector
{
    protected $connection;
    public $status;
    protected $inTransaction = false;

    public function __construct($host, $user, $password, $database, $port=3306)
    {
        $this->database = $database;

        $this->connection = oci_pconnect($user, $password, 
            '(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = '.$host.')
            (PORT = '.$port.')) (CONNECT_DATA = (SID = '.$database.')) )');

        if (!$this->connection) {
            throw new Exception(oci_error());
        }

    }

    public function isInTransaction()
    {
        return $this->inTransaction;
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

    public function getLastId()
    {
        return 0; // $this->connection->lastInsertId();
    }

    public function _execUnpreparedSQL($sql)
    {
        if (config('app.debug_info')) {
            global $debuginfo;
            
            $debuginfo['queryes'][] = $sql; // preg_replace('/\s\s+/', ' ', str_replace("'", "\"", $sql));
        }

        $stmnt = oci_parse($this->connection, $sql);

        oci_execute($stmnt);

        $this->status = oci_num_rows($stmnt);

        $this->lastId = 0;

        oci_free_statement($stmnt);

        return true;
    }

    public function _execSQL($sql, $parent, $fill=false)
    {
        $sql = str_replace("`", "", $sql);
        
        if (strpos($sql, ' LIMIT ')!==false) {

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

        $bindings = Builder::__joinBindings($bindings);


        if (config('app.debug_info')) {
            global $debuginfo;

            $result = Builder::__getPlainSqlQuery($sql, $bindings);

            $debuginfo['queryes'][] = $result; // preg_replace('/\s\s+/', ' ', str_replace("'", "\"", $sql));
        }

        $count = 0;
        $new_bindings = array();

        while (strpos($sql, '?')!==false) {
            $sql = preg_replace('/\?/', ':baradur_'.$count, $sql, 1);
            $new_bindings['baradur_'.$count] = $bindings[$count];
            $count++;
        }

        $stmnt = oci_parse($this->connection, $sql);

        foreach ($new_bindings as $key => $val) {
            oci_bind_by_name($stmnt, $key, $new_bindings[$key]);
        }

        if ($this->inTransaction) {
            if ( !oci_execute($stmnt, OCI_DEFAULT) ) {
                $error = oci_error($stmnt);
                throw new Exception($error['message']);
            }
        } else {
            $query = oci_execute($stmnt);
        }

        if (!$query) {
            $error = oci_error($stmnt);
            throw new Exception($error['message']);
        }

        $this->status = oci_num_rows($stmnt);
        
        $this->lastId = 0;

        if ($fill) {
            while (($r = oci_fetch_object($stmnt)) != false) {
                if (!$parent->_toBase) {
                    $parent->_collection->put($this->objetToModel($r, $parent));
                } else {
                    unset($r->BARADUR_ROWINDEX);
                    $parent->_collection->put($r);
                }
            }

            oci_free_statement($stmnt);
    
            return $parent->_collection;
        }

        oci_free_statement($stmnt);

        return true;
    }

    private function arrayToObject($data)
    {
        $obj = new stdClass;
        
        foreach ($data as $key => $val) {
            $obj->$key = $val;
        }

        return $obj;
    }
    
}