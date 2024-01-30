<?php

trait Sushi
{
    protected static $sushiConnection = null;
    
    protected static $cachePath = null;

    protected $connection = 'sqlite';

    protected $schema = array();

    public $sushiInsertChunkSize = 100;

    public function getRows()
    {
        return $this->rows;
    }

    public function getSchema()
    {
        return $this->schema ? $this->schema : array();
    }

    protected function sushiCacheReferencePath()
    {
        return null;
    }

    public function __getConnector($connection=null)
    {
        if (self::$sushiConnection) {
            return self::$sushiConnection;
        }

        $connection = 'sqlite';
        $config = config('database.connections.' . $connection);

        self::$sushiConnection = new SqliteConnector($config);

        return self::$sushiConnection;
    }

    private function getSushiInsertChunkSize() {
        return $this->sushiInsertChunkSize;
    }

    private function getRowType($key, $value)
    {
        $schema = $this->getSchema();

        if (isset($schema[$key])) {
            return $schema[$key];
        }

        switch (true) {
            case is_int($value):
                $type = 'integer';
                break;
            case is_numeric($value):
                $type = 'float';
                break;
            case is_string($value):
                $type = 'string';
                break;
            default:
                $type = 'string';
        }

        return $type;
    }

    public function retrieveRowValues($row)
    {
        $values = array();

        foreach ($row as $key => $value) {
            $values[] = in_array($this->getRowType($key, $value), array('integer', 'float')) 
                ? $value
                : "'" . $value . "'";
        }

        return $values;
    }

    public function bootSushi()
    {
        $version = config('database.connections.sqlite.driver');
        $cacheDirectory = storage_path('framework/cache');
        self::$cachePath = is_writable($cacheDirectory) ? $cacheDirectory.'/sushi.sqlite' : ':memory:';
        $dataPath = $this->sushiCacheReferencePath();

        if (file_exists(self::$cachePath)) {

            $dbtime = filemtime(self::$cachePath);
            $ftime = $dataPath ? filemtime($dataPath) : filemtime(__FILE__);

            if ($dbtime > $ftime) {
                return;
            }
        }
        
        $result = $this->getQuery()->from('sqlite_master')->where('type', 'table')->where('name', $this->getTable())->first();
        
        if ($result) {
            $sql = 'DROP TABLE '. $this->getTable() . ';';
            $this->getQuery()->from($this->getTable())->connector()->execUnpreparedSQL($sql);
        }

        $sql = 'CREATE TABLE '. $this->getTable() . ' (';

        $rows = reset($this->getRows());

        $cols = array();
        $colnames = array();

        foreach($rows as $key => $value) {
            $type = $this->getRowType($key, $value);
            $cols[] = "'$key' $type";
            $colnames[] = $key;
        }

        if (count($cols)==0) {
            foreach ($this->schema as $key => $value) {
                $cols[] = "'$key' $value";
                $colnames[] = $key;
            }
        }

        if ($this->usesTimestamps()) {
            $created = $this->getCreatedAtColumn();
            
            if (!in_array($created, $colnames)) {
                $cols[] = "'$created' timestamp";
                $colnames[] = $created;

                $schema = $this->getSchema();
                $schema[$created] = 'timestamp';
                $this->schema = $schema;
            }
        }
        
        $sql .= implode(', ', $cols) . ');';

        $result = $this->getQuery()->connector()->execUnpreparedSQL($sql);

        if (!$result) {
            throw new Exception('Error creating sushi database');
        }


        $sql = 'INSERT INTO ' . $this->getTable() . ' (' . implode(', ', $colnames) . ')';

        $count = 1;
        $now = now()->timestamp;

        foreach($this->getRows() as $row) {

            if ($this->usesTimestamps() && count($row)<count($colnames)) {
                $row[$this->getCreatedAtColumn()] = $now;
            } 
            
            $sql .= ($count > 1 ? ', ' : ' VALUES ') . '(' . implode(', ', $this->retrieveRowValues($row)) . ')';
            $count++;

            // Chunk only supported in SQlite 3

            if ($count>$this->getSushiInsertChunkSize() || $version=='sqlite2') {
                $sql .= ';'; //dump($sql);
                $result = $this->getQuery()->connector()->execUnpreparedSQL($sql);
                $sql = 'INSERT INTO ' . $this->getTable() . '(' . implode(', ', $colnames) . ')';
                $count = 1;
            }
        }

        if ($count > 1) {
            $sql .= ';'; //dump($sql);
            $result = $this->getQuery()->connector()->execUnpreparedSQL($sql);
        }

    }
}