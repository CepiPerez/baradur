<?php

Class Connector
{
    /** @return int|bool */
    /* public function query($sql, $bindings=array())
    {
        return $this->execSQL($sql, $bindings, false);
    } */

    protected $database;
    protected $lastId = null;


    public function getDatabase()
    {
        return $this->database;
    }


    public function execUnpreparedSQL($sql)
    {
        try {
            return $this->_execUnpreparedSql($sql);
        }
        catch (Exception $e) {

            if (config('app.debug')) {
                throw new QueryException($e->getMessage());
            }

            return false;
        }
    }

    public function execSQL($sql, $parent, $fill=false)
    {
        if ($fill && $parent->_collection==null) {
            $parent->_collection = new Collection();
        }
        
        try {
            return $this->_execSQL($sql, $parent, $fill);
        }
        catch (Exception $e) {

            if (config('app.debug')) {
                throw new QueryException($e->getMessage());
            }

            return false;
        }
    }
    
    protected function objetToModel($data, $builder)
    {
        unset($data->BARADUR_ROWINDEX);

        /* if ($builder->_softDelete) {
            unset($data->deleted_at);
        } */

        $class = $builder->_parent;
        $item = new $class($data);

        /* $item->setAttributes(CastHelper::processCasts(
            (array)$data,
            $builder->_model,
            false
        )); */

        /* foreach ($data as $key => $val) {
            $item->setAttribute($key, $val);
        } */
        
        //$item->__parseAccessorAttributes();

        $item->_setOriginalRelations($builder->_eagerLoad);
        $item->syncOriginal();

        //unset($item->_global_scopes);
        //unset($item->timestamps);

        return $item;
    }
}