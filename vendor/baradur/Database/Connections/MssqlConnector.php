<?php

class MssqlConnector extends Connector
{
    protected $connection;
    public $status;
    protected $inTransaction = false;
    protected $lastId = null;

    public function __construct($config)
    {
        $host = isset($config['host']) ? $config['host'] : 'localhost';
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $port = isset($config['port']) ? $config['port'] : 1433;

        // Formato para mssql_connect
        $server = $host . ':' . $port;

        try {
            $this->connection = mssql_connect($server, $username, $password);

            if (!$this->connection) {
                throw new Exception('No se pudo conectar al servidor');
            }

            $selected = mssql_select_db($database, $this->connection);
            if (!$selected) {
                throw new Exception('No se pudo seleccionar la base de datos');
            }
        } catch (Exception $e) {
            throw new Exception("Error al conectar a SQL Server: " . $e->getMessage());
        }
    }

    public function isInTransaction()
    {
        return $this->inTransaction;
    }

    public function beginTransaction()
    {
        $this->inTransaction = true;
        $this->_execUnpreparedSQL("BEGIN TRANSACTION");
    }

    public function commit()
    {
        $this->_execUnpreparedSQL("COMMIT");
        $this->inTransaction = false;
    }

    public function rollBack()
    {
        $this->_execUnpreparedSQL("ROLLBACK");
        $this->inTransaction = false;
    }

    public function getLastId()
    {
        try {
            $result = $this->_execUnpreparedSQLReturn("SELECT @@IDENTITY AS LastId");
            if (!empty($result) && isset($result[0]->LastId)) {
                return intval($result[0]->LastId);
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function _execUnpreparedSQL($sql)
    {
        // Limpiar backticks para SQL Server
        $sql = $this->cleanSqlForSqlServer($sql);

        if (config('app.debug_info')) {
            global $debuginfo;
            $debuginfo['queryes'][] = $sql;
        }

        $result = mssql_query($sql, $this->connection);

        if ($result === false) {
            throw new Exception("Error en consulta: " . mssql_get_last_message());
        }

        $this->status = mssql_rows_affected($this->connection);
        $this->lastId = $this->getLastId();

        return true;
    }

    public function _execUnpreparedSQLReturn($sql)
    {
        // Limpiar backticks para SQL Server
        $sql = $this->cleanSqlForSqlServer($sql);

        if (config('app.debug_info')) {
            global $debuginfo;
            $debuginfo['queryes'][] = $sql;
        }

        $result = mssql_query($sql, $this->connection);

        if ($result === false) {
            throw new Exception("Error en consulta: " . mssql_get_last_message());
        }

        $rows = array();
        while ($row = mssql_fetch_object($result)) {
            $rows[] = $row;
        }

        mssql_free_result($result);

        return $rows;
    }

    public function _execSQL($sql, $parent, $fill = false)
    {
        $bindings = $parent instanceof Builder ? $parent->_bindings : $parent;
        $bindings = Builder::__joinBindings($bindings);

        // Limpiar backticks para SQL Server
        $sql = $this->cleanSqlForSqlServer($sql);

        if (config('app.debug_info')) {
            global $debuginfo;
            $result = Builder::__getPlainSqlQuery($sql, $bindings);
            $debuginfo['queryes'][] = $result;
        }

        // Binding manual
        $boundSql = $this->_bindParameters($sql, $bindings);

        $result = mssql_query($boundSql, $this->connection);

        if ($result === false) {
            throw new Exception("Error en consulta: " . mssql_get_last_message());
        }

        $this->status = mssql_rows_affected($this->connection);
        $this->lastId = $this->getLastId();

        if ($fill) {
            while ($row = mssql_fetch_object($result)) {
                if (!$parent->_toBase) {
                    $parent->_collection->put($this->objetToModel($row, $parent));
                } else {
                    $parent->_collection->put($row);
                }
            }
            mssql_free_result($result);
            return $parent->_collection;
        }

        if (is_resource($result)) {
            mssql_free_result($result);
        }

        return true;
    }

    public function getRowSet($sql, $bindings = array())
    {
        $bindings = is_array($bindings) ? $bindings : array($bindings);

        // Limpiar backticks para SQL Server
        $sql = $this->cleanSqlForSqlServer($sql);

        // Binding manual
        $boundSql = $this->_bindParameters($sql, $bindings);

        if (config('app.debug_info')) {
            global $debuginfo;
            $debuginfo['queryes'][] = $boundSql;
        }

        $result = mssql_query($boundSql, $this->connection);

        if ($result === false) {
            return array();
        }

        $sets = array();
        $resultSet = array();

        while ($row = mssql_fetch_object($result)) {
            $resultSet[] = $row;
        }

        $sets[] = $resultSet;
        mssql_free_result($result);

        return $sets;
    }

    protected function _bindParameters($sql, $bindings)
    {
        if (empty($bindings)) {
            return $sql;
        }

        $boundSql = $sql;
        foreach ($bindings as $value) {
            if (is_string($value)) {
                $value = "'" . str_replace("'", "''", $value) . "'";
            } elseif (is_null($value)) {
                $value = 'NULL';
            } elseif (is_bool($value)) {
                $value = $value ? 1 : 0;
            } elseif (is_int($value) || is_float($value)) {
                // Los números se dejan tal cual
            } else {
                // Para otros tipos, los convertimos a string
                $value = "'" . str_replace("'", "''", strval($value)) . "'";
            }
            $boundSql = preg_replace('/\?/', $value, $boundSql, 1);
        }

        return $boundSql;
    }

    protected function cleanSqlForSqlServer($sql)
    {
        // Reemplazar backticks (`) por espacios o dejar sin delimitadores
        // SQL Server no necesita delimitadores para nombres simples
        $sql = str_replace('`', '', $sql);

        // Reemplazar funciones MySQL específicas si es necesario
        // Por ejemplo: CONVERT en lugar de DATE_FORMAT, etc.

        return $sql;
    }

    protected function isUniqueConstraintError($exception)
    {
        $message = $exception->getMessage();
        return strpos($message, 'Cannot insert duplicate key row') !== false ||
            strpos($message, 'Violation of PRIMARY KEY constraint') !== false ||
            strpos($message, 'duplicate key') !== false;
    }

    protected function objetToModel($object, $parent)
    {
        // Aquí va tu lógica personalizada de mapeo objeto -> modelo
        return $object;
    }

    public function __destruct()
    {
        if ($this->connection) {
            mssql_close($this->connection);
        }
    }
}
