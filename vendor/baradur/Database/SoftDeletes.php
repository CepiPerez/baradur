<?php

trait SoftDeletes
{
    protected $useSoftDeletes = true;

    protected $_trashed = null;

    public function _setTrashed($val)
    {
        if (!$this->useSoftDeletes)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');

        $this->_trashed = $val;
    }

    public function trashed()
    {
        if (!$this->useSoftDeletes)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');

        return isset($this->_trashed);
    }

    
    /**
     * Soft-deletes the current model from database
     * 
     * @return bool
     */
    public function delete()
    {
        if (!$this->useSoftDeletes)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');

        /* if( isset($this) && $this instanceof self )
        { */
            if (count($this->_original)==0)
                throw new Exception('Error! Trying to delete new Model');

            return $this->getQuery()->softDeletes($this->_original);
        /* } */
    }

    /**
     * Restore the trashed model
     * 
     * @return bool
     */
    public function restore()
    {
        if (!$this->useSoftDeletes)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');

        if( isset($this) && $this instanceof self )
        {
            if (count($this->_original)==0)
                throw new Exception('Error! Trying to delete new Model');

            return $this->getQuery()->restore($this->_original);
        }
    }

    /**
     * Permanently deletes the trashed model
     * 
     * @return bool
     */
    public function forceDelete()
    {
        if (!$this->useSoftDeletes)
            throw new Exception('Trying to use softDelete method on a non-softDelete Model');

        if( isset($this) && $this instanceof self )
        {
            if (count($this->_original)==0)
                throw new Exception('Error! Trying to delete new Model');

            return $this->getQuery()->forceDelete($this->_original);
        }
    }

}