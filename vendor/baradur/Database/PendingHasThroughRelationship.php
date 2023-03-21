<?php

class PendingHasThroughRelationship
{
    protected $rootModel;

    protected $localRelationship;

    public function __construct($rootModel, $localRelationship)
    {
        $this->rootModel = $rootModel;

        $this->localRelationship = $localRelationship;
    }

    public function has($callback)
    {
        $primary = $this->localRelationship->_relationVars['primary'];
        $foreign = $this->localRelationship->_relationVars['foreign'];
        
        $through = $this->localRelationship->_model->{$callback}();

        $class_through = $through->_relationVars['class'];
        $primary_through = $through->_relationVars['primary'];
        $foreign_through = $through->_relationVars['foreign'];
        $relationship = $through->_relationVars['relationship'];

        //dd($this->rootModel);

        return Relations::hasManyThrough(
            $this->rootModel->getQuery(),
            $through->_parent,
            $class_through,
            $foreign, 
            $foreign_through, 
            $primary, 
            $primary_through
        );

        return $this->rootModel->{$relationship.'Through'}(
            $through->_parent, $class_through, $foreign, $foreign_through, $primary, $primary_through
        );

        /* $distantRelation = $callback($this->localRelationship->getRelated());

        if ($distantRelation instanceof HasMany) {
            return $this->rootModel->hasManyThrough(
                $distantRelation->getRelated()::class,
                $this->localRelationship->getRelated()::class,
                $this->localRelationship->getForeignKeyName(),
                $distantRelation->getForeignKeyName(),
                $this->localRelationship->getLocalKeyName(),
                $distantRelation->getLocalKeyName(),
            );
        }

        return $this->rootModel->hasOneThrough(
            $distantRelation->getRelated()::class,
            $this->localRelationship->getRelated()::class,
            $this->localRelationship->getForeignKeyName(),
            $distantRelation->getForeignKeyName(),
            $this->localRelationship->getLocalKeyName(),
            $distantRelation->getLocalKeyName(),
        ); */
    }

    public function __call($method, $parameters)
    {
        if (Str::startsWith($method, 'has')) {
            return $this->has(Str::of($method)->after('has')->lcfirst()->toString());
        }

        throw new BadMethodCallException(
            'Call to undefined method '.$this->localRelationship.'::'.$method.'()'
        );
    }
}