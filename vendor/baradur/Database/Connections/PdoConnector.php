<?php

Class PdoConnector extends Connector
{
    protected $connection;
    public $status;
    protected $inTransaction = false;

    public function __construct($host, $user, $password, $database, $port=3306)
    {
        $this->database = $database;

        try {
            $this->connection = new PDO("mysql:host=$host; dbname=$database", $user, $password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));

            $this->connection->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e) {
            throw new Exception($e->getMessage());
        }

    }

    public function isInTransaction()
    {
        return $this->inTransaction;
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

    public function getLastId()
    {
        return $this->connection->lastInsertId();
    }

    public function _execUnpreparedSQL($sql)
    {
        if (config('app.debug_info')) {
            global $debuginfo;
            
            $debuginfo['queryes'][] = $sql; // preg_replace('/\s\s+/', ' ', str_replace("'", "\"", $sql));
        }

        $query = $this->connection->query($sql);

        $this->lastId = $this->connection->lastInsertId();

        $this->status = $query->rowCount();

        return true;
    }
    
    public function _execSQL($sql, $parent, $fill=false)
    {    
        $bindings = $parent instanceof Builder? $parent->_bindings : $parent;

        $bindings = Builder::__joinBindings($bindings);
        
        //dump($query); dump($bindings);

        if (config('app.debug_info')) {
            global $debuginfo;

            $result = Builder::__getPlainSqlQuery($sql, $bindings);
            
            $debuginfo['queryes'][] = $result; // preg_replace('/\s\s+/', ' ', str_replace("'", "\"", $sql));
        }

        $query = $this->connection->prepare($sql);

        $query->execute($bindings);

        $this->status = $query->rowCount();

        $this->lastId = $this->connection->lastInsertId();
        
        if ($fill) {
            while( $r = $query->fetchObject() ) {
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

    public function getRowSet($sql, $bindings=array())
    {
        $bindings = is_array($bindings) ? $bindings : array($bindings);

        foreach ($bindings as $val) {
            if (is_string($val)) {
                $val = "'$val'";
            }
            $sql = preg_replace('/\?/', $val, $sql, 1);
        }

        $query = $this->connection->query($sql);
        
        $sets = array();
        
        try {
            while ($res = $query->fetchAll(PDO::FETCH_OBJ)) {
                $sets[] = $res;
                $query->nextRowset();
            }
        } catch (Exception $e) {
            return $sets;
        }

        return $sets;
    }

}