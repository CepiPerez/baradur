<?php

trait Prunable
{

    public function prunable()
    {
        throw new LogicException('Please implement the prunable method on your model.');
    }

    public function prune()
    {
        $this->pruning();

        return method_exists(get_class($this), 'forceDelete')
            ? $this->forceDelete()
            : $this->delete();
    }

    protected function pruning()
    {
        //
    }
}