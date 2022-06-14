<?php

Class Connector
{
    protected $connection;
    public $error;

    public function __construct($host, $user, $password, $database, $port=3306)
    {
        $this->connection = new mysqli($host, $user, $password, $database, $port);
        $this->connection->set_charset("utf8");
        mysqli_query($this->connection, "SET NAMES 'utf8'");
        mysqli_report(MYSQLI_REPORT_OFF);
        if (!$this->connection) {
            throw new Exception("Error trying to connect to database");
        }
    }


    public function error()
    {
        return $this->error;
    }

    public function query($sql)
    {
        try
        {
            return $this->_query($sql);
        }
        catch (Exception $e) 
        {
            if (env('APP_DEBUG')==true) throw new Exception($e->getMessage());

            return false;
        }
    }

    public function _query($sql)
    {
        //echo "SQL VIEJO: ".$sql;
        //$sql = str_replace("'NOW()'", "NOW()", $sql);
        
        $query = $this->connection->query($sql);

        //dd($this->connection->insert_id); 

        if (!$query) {
            throw new Exception($this->connection->error);
        }
        return $query;
    }

    public function getLastId()
    {
        return $this->connection->insert_id;
    }

    public function execSQL($sql, $wherevals=array(), $collection=null)
    {
        //var_dump($wherevals);
        if ($collection==null)
            $collection = new Collection('stdClass');
        
        try
        {
            return $this->_execSQL($sql, $wherevals, $collection);
        }
        catch (Exception $e) 
        {
            if (env('APP_DEBUG')==true) throw new Exception($e->getMessage());

            return false;
        }
    }
    
    public function _execSQL($sql, $wherevals=array(), $collection=null)
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
        $query = $this->connection->query($sql);

        $this->error = $this->connection->error;

        if (!$query) {
            throw new Exception($this->connection->error);
        }

        
        if (!is_bool($query))
        {
            while( $r = $query->fetch_object() )
            {
                $c = $collection->getParent();

                $obj = new $c;
                foreach ($r as $key => $val)
                    $obj->$key = $val;


                $collection->put($obj);

            }
            return $collection;
        }

        return $query;

            
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


}