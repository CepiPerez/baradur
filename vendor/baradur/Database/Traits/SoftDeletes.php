<?php

trait SoftDeletes
{
    protected $_useSoftDeletes = true;

    protected $_trashed = null;

    protected $_DELETED_AT = 'deleted_at';

    public function _setTrashed($val)
    {
        if (!$this->_useSoftDeletes) {
            throw new BadMethodCallException('Trying to use softDelete method on a non-softDelete Model');
        }

        $this->_trashed = $val;
    }

    public function trashed()
    {
        if (!$this->_useSoftDeletes) {
            throw new BadMethodCallException('Trying to use softDelete method on a non-softDelete Model');
        }

        return isset($this->_trashed);
    }
    
    /**
     * Soft-deletes the current model from database
     * 
     * @return bool
     */
    public function delete()
    {
        $this->checkObserver('deleting', $this);

        if (count($this->original)==0) {
            throw new LogicException('Error! Trying to delete new Model');
        }

        $res = $this->getQuery()->softDeletes($this->original);

        if ($res) {
            $this->checkObserver('deleted', $this);
        }

        return $res;
    }

    /**
     * Restore the trashed model
     * 
     * @return bool
     */
    public function restore()
    {
        $this->checkObserver('restoring', $this);

        if (!$this->_useSoftDeletes) {
            throw new BadMethodCallException('Trying to use softDelete method on a non-softDelete Model');
        }

        if (count($this->original)==0) {
            throw new LogicException('Error! Trying to delete new Model');
        }

        $res = $this->getQuery()->restore($this->original);

        if ($res) {
            $this->checkObserver('restored', $this);
        }

        return $res;
    }

    /**
     * Permanently deletes the trashed model
     * 
     * @return bool
     */
    public function forceDelete()
    {
        $this->checkObserver('forceDeleting', $this);

        if (!$this->_useSoftDeletes) {
            throw new BadMethodCallException('Trying to use softDelete method on a non-softDelete Model');
        }

        if (count($this->original)==0) {
            throw new LogicException('Error! Trying to delete new Model');
        }

        $res = $this->getQuery()->forceDelete($this->original);

        if ($res) {
            $this->checkObserver('forceDeleted', $this);
        }
        
        return $res;
    }

    public function getDeletedAtColumn()
    {
        return $this->_DELETED_AT;
    }

}