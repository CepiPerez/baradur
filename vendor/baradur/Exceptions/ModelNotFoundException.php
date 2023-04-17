<?php

class ModelNotFoundException extends RecordsNotFoundException
{
    protected $model;
    protected $ids;

    public function setModel($model, $ids = array())
    {
        $this->model = $model;
        $this->ids = Arr::wrap($ids);

        $this->message = "No query results for model [{$model}]";

        if (count($this->ids) > 0) {
            $this->message .= ' '.implode(', ', $this->ids);
        } else {
            $this->message .= '.';
        }

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getIds()
    {
        return $this->ids;
    }

}
