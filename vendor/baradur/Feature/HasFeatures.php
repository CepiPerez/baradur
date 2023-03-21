<?php

trait HasFeatures
{
    public function features()
    {
        return Feature::for($this);
    }
}