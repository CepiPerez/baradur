<?php

Class Relations
{
    private static function getInstance($class, $parent)
    {
        $res = null;

        if (class_exists($class))
        {
            $res = Model::instance($class);
        }
        else
        {
            $res = DB::table(Helpers::camelCaseToSnakeCase($class, false));
            
            if ($parent->_connector) 
                $res->setConnector($parent->_connector);
        }

        return $res;
    }

    private static function addExtraQuery($query, $parent)
    {
        if ($parent->_extraQuery)
        {
            list($class, $method, $params) = getCallbackFromString($parent->_extraQuery);
            array_shift($params);
            executeCallback($class, $method, array_merge(array($query), $params), null);
            //call_user_func_array(array($class, $method), array_merge(array($query), $params));
        }
    }

    private static function addNextRelations($query, $parent)
    {
        if ($parent->_nextRelation)
        {
            foreach ($parent->_nextRelation as $k => $v) 
            {
                if ($k!='_constraints')
                    $query->_eagerLoad[$k] = $v;
            }

        }
    }



    public static function hasOne($parent, $class, $foreign, $primary)
    {
        $res = self::getInstance($class, $parent);

        if (!$foreign)
        {
            $foreign = Helpers::camelCaseToSnakeCase($parent->_parent, false).'_id';
        }
        
        if (!$primary) 
        {
            $primary = is_array($parent->_primary)? $parent->_primary[0] : $parent->_primary;
        }

        $res->_relationVars = array(
            'relationship' => 'hasOne',
            'class' => $parent->_parent,
            'foreign' => $foreign,
            'primary' => $primary,
        );

        if ($parent->_collection->count()>0)
        {
            $wherein = array();
            $wherein = (array)$parent->_collection->pluck($primary);
            $res->whereIn($res->_table.'.'.$foreign, $wherein);
            $res->_relationVars['where_in'] = $wherein;
        }

        self::addExtraQuery($res, $parent);

        self::addNextRelations($res, $parent);

        return $res;

    }

    public static function hasMany($parent, $class, $foreign, $primary)
    {
        $res = self::hasOne($parent, $class, $foreign, $primary);
        $res->_relationVars['relationship'] = 'hasMany';
        return $res;
    }

    public static function belongsTo($parent, $class, $foreign, $primary)
    {

        $res = self::getInstance($class, $parent);

        if (!$foreign) 
        {
            $foreign = 'id';
        }
            
        if (!$primary) 
        {
            $primary = Helpers::camelCaseToSnakeCase($res->_parent, false).'_id';
        }

        $res->_relationVars = array(
            'relationship' => 'belongsTo',
            'class' => $parent->_parent,
            'foreign' => $foreign,
            'primary' => $primary,
            'collection' => $parent->_collection
        );

        if ($parent->_collection->count()>0)
        {
            $wherein = array();
            $wherein = (array)$parent->_collection->pluck($primary);
            $res->whereIn($res->_table.'.'.$foreign, $wherein);
            $res->_relationVars['where_in'] = $wherein;
        }

        //dd($res);

        self::addExtraQuery($res, $parent);

        self::addNextRelations($res, $parent);

        return $res;
    }
    
    public static function hasOneThrough($parent, $class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough)
    {
        $res = self::getInstance($class, $parent);

        $secondarytable = Helpers::camelCaseToSnakeCase($classthrough, false);

        if (class_exists($classthrough))
            $secondarytable = call_user_func(array($classthrough, 'getTable'), array());
        

        $res = $res->join($secondarytable, $primarythrough, '=', $foreign);

        $res->_relationVars = array(
            'relationship' => 'hasOneThrough',
            'class' => $parent->_parent,
            'classthrough' => $classthrough,
            'foreignthrough' => $foreignthrough,
            'primarythrough' => $primarythrough,
            'foreign' => $foreign,
            'primary' => $primary
        );
        

        if ($parent->_collection->count()>0)
        {
            $wherein = array();
            $wherein = (array)$parent->_collection->pluck($primary);
            $res = $res->whereIn($foreignthrough, $wherein);
            $res->_relationVars['where_in'] = $wherein;
        }

        
        self::addExtraQuery($res, $parent);

        self::addNextRelations($res, $parent);

        return $res;
    }

    public static function hasManyThrough($parent, $class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough)
    {
        $res = self::hasOneThrough($parent, $class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough);
        $res->_relationVars['relationship'] = 'hasManyThrough';
        return $res;
    }

    public static function belongsToMany($parent, $class, $foreign, $primary, $foreignthrough, $primarythrough)
    {
        $array = array($parent->_parent, $class);
        sort($array);                 
        $classthrough = Helpers::camelCaseToSnakeCase(implode('', $array), false);

        if (!$foreignthrough)
        {
            $foreignthrough = Helpers::camelCaseToSnakeCase($parent->_parent, false).'_'.$parent->_routeKey;
        } 

        if (!$primarythrough)
        {
            $primarythrough = Helpers::camelCaseToSnakeCase($class, false).'_'.$parent->_routeKey;
        }

        if (!$foreign)
        {
            $foreign = 'id';
        }

        if (!$primary) 
        {
            $primary = is_array($parent->_primary)? $parent->_primary[0] : $parent->_primary;
        }

        $res = self::hasOneThrough($parent, $class, $classthrough, $foreignthrough, $foreign, $primary, $primarythrough);
        $res->_relationVars['relationship'] = 'belongsToMany';

        if ($parent->_collection->count()==1)
        {
            $current = $parent->_collection->first()->$primary;
            $res->_relationVars['current'] = $current;
        }

        return $res;

    }

    public static function morphOne($parent, $class, $method)
    {
        $res = self::getInstance($class, $parent);

        $primary = is_array($parent->_primary)? $parent->_primary[0] : $parent->_primary;
        
        //$newmodel = Model::instance($class);
        $res = $res->where($method.'_type', $parent->_parent);

        $res->_relationVars = array(
            'relationship' => 'morphOne',
            'foreign' => $method.'_id',
            'primary' => $primary,
            'relation_type' => $method.'_type',
            'current_type' => $parent->_parent
        );

        if ($parent->_collection->count()>0)
        {
            $wherein = (array)$parent->_collection->pluck($primary);
            $res = $res->whereIn($method.'_id', $wherein);
            $res->_relationVars['current_id'] = $parent->_collection->first()->$primary;
            $res->_relationVars['where_in'] = $wherein;
        }
            
        return $res;
    }

    public static function morphMany($parent, $class, $method)
    {
        $res = self::morphOne($parent, $class, $method);
        $res->_relationVars['relationship'] = 'morphMany';
        return $res;
    }

    public static function morphTo($parent)
    {        
        $primary = is_array($parent->_primary)? $parent->_primary[0] : $parent->_primary;

        $keys = array_keys((array)$parent->_collection->first());

        $type = null;
        $id = null;
        foreach ($keys as $key)
        {
            if (substr($key, -3)=='_id') $id = $key;
            if (substr($key, -5)=='_type') $type = $key;
        }

        if (!$type || !$id)
            return null;

        $classname = $parent->_collection->pluck($type)->first();
        $wherein = (array)$parent->_collection->pluck($id);

        $res = self::getInstance($classname, $parent);
        
        $res = $res->whereIn($primary, $wherein);
        $res->_relationVars['where_in'] = $wherein;

        $res->_relationVars = array(
            'foreign' => $primary,
            'primary' => $id,
            'relationship' => 'morphTo');

        //dump($res->_relationVars);

        return $res;
    }

    public static function morphToMany($parent, $class, $method)
    {
        $res = self::getInstance($class, $parent);
        
        $primary = is_array($parent->_primary)? $parent->_primary[0] : $parent->_primary;
        $secondary = Helpers::getPlural($method);

        $arr = array();
        $arr['id'] = Helpers::camelCaseToSnakeCase($class, false).'_id';
        $arr['related'] = $method.'_id';
        $arr['type'] = $method.'_type';

        $res->_relationVars = array(
            'relationship' => 'morphToMany',
            'foreign' => $arr['id'],
            'primary' => $primary,
            'foreignthrough' => $arr['related'],
            'primarythrough' => $primary,
            'relation_type' => $arr['type'],
            'classthrough' => $secondary,
            'current_type' => $parent->_parent
        );
        
        $res = $res->where($secondary.'.'.$arr['type'], $parent->_parent)
            ->join($secondary, $arr['id'], '=', 'id');
        

        if ($parent->_collection->count()>0)
        {
            $wherein = (array)$parent->_collection->pluck($primary);
            $res = $res->whereIn($arr['related'], $wherein);
            $res->_relationVars['current_id'] = $parent->_collection->first()->$primary;
            $res->_relationVars['where_in'] = $wherein;
        }

        return $res;
    }

    public static function morphedByMany($parent, $class, $method)
    {
        $res = self::getInstance($class, $parent);
        
        $primary = is_array($parent->_primary)? $parent->_primary[0] : $parent->_primary;
        $secondary = Helpers::getPlural($method);

        $arr = array();
        $arr['id'] = $res->_primary[0];
        $arr['related'] = $method.'_id';
        $arr['type'] = $method.'_type';

        $res->_relationVars = array(
            'relationship' => 'morphedByMany',
            'foreign' => $arr['id'],
            'primary' => Helpers::camelCaseToSnakeCase($parent->_parent, false).'_id',
            'foreignthrough' => $arr['related'],
            'primarythrough' => $primary,
            'relation_type' => $arr['type'],
            'classthrough' => $secondary,
            'current_type' => $parent->_parent
        );

        
        $res = $res->where($secondary.'.'.$arr['type'], $class)
            ->join($secondary, $arr['related'], '=', $arr['id']);
    
        if ($parent->_collection->count()>0)
        {
            $wherein = (array)$parent->_collection->pluck($primary);
            $res = $res->whereIn($res->_relationVars['primary'], $wherein);
            $res->_relationVars['current_id'] = $parent->_collection->first()->$primary;
            $res->_relationVars['where_in'] = $wherein;
        }

        return $res;
    }


    public static function insertRelation($parent, $res, $relation)
    {
        $classthrough = $res->_relationVars['classthrough'];
        $relationship = $res->_relationVars['relationship'];
        $foreignthrough = $res->_relationVars['foreignthrough'];
        $primarythrough = $res->_relationVars['primarythrough'];
        $foreign = $res->_relationVars['foreign'];
        $primary = $res->_relationVars['primary'];
        $oneOfMany = $res->_relationVars['oneOfMany'];

        $extra_columns = $res->_relationVars['extra_columns'];
        $pivot_name = isset($res->_relationVars['pivot_name'])?
            $res->_relationVars['pivot_name'] : 'pivot';
        
        if (isset($parent->_eagerLoad[$relation]['_constraints'])
            && !isset($res->_eagerLoad))
        {
            $res->_eagerLoad = $parent->_eagerLoad[$relation]['_constraints']->_eagerLoad;
        }

        if (isset($parent->_hasConstraints))
        {
            $r = $parent->_hasConstraints['relation'];
            $r = str_replace($parent->_relationName.'.', '', $r);
            $c = $parent->_hasConstraints['constraints'];
            $res->_has($r, $c);
        }

        $columns = $parent->_relationColumns;
        $relation = $parent->_relationName;
        //dump($parent); dump($res); 

        if (in_array($relationship, array('hasOneThrough', 'hasManyThrough')))
        {
            $res->addSelect("$classthrough.$foreignthrough as bardur_through_key");
            $key = 'bardur_through_key';

            if (is_array($columns) && !in_array($key, $columns))
            {
                $columns[] = $key;
            }

        }
        elseif ($relationship == 'belongsToMany')
        {
            $res->addSelect("$classthrough.$foreignthrough as pivot_$foreignthrough")
                ->addSelect("$classthrough.$primarythrough as pivot_$primarythrough");
            $key = "pivot_$foreignthrough";
        }
        elseif ($relationship == 'morphToMany')
        {
            $res->addSelect("$classthrough.$foreignthrough as pivot_$foreignthrough")
                ->addSelect("$classthrough.$foreign as pivot_$foreign");
            $key = "pivot_$foreignthrough";
        }
        elseif ($relationship == 'morphedByMany')
        {
            $res->addSelect("$classthrough.$foreignthrough as pivot_$foreignthrough")
                ->addSelect("$classthrough.$primary as pivot_$primary");
            $key = "pivot_$primary";
        }
        else
        {
            $key = $foreign;
        }

        if ($extra_columns && in_array($relationship, array('belongsToMany', 'morphToMany', 'morphedByMany')))
        {
            foreach ($extra_columns as $ec)
                $res->addSelect("$classthrough.$ec as pivot_$ec");
        }

        //dump($res);
        $res = $res->get();
        //dump($res); //dump($parent);

        if ($relationship=='morphedByMany')
        {
            $primary = $parent->_primary[0];
        }

        //dump("KEY: $key");
        //dump($res);
        //dump($parent);

        $to_remove = array();

        foreach ($parent->_collection as $current)
        {

            if (in_array($relationship, array('morphToMany', 'morphedByMany', 'belongsToMany')))
            {
                $results = $columns!='*'
                    ? $res->where($key, $current->$primary)->keys($columns) 
                    : $res->where($key, $current->$primary);

                foreach ($results as $r)
                {
                    if ($parent->_toBase)
                    {
                        $pivot = new stdClass;
                        foreach ($r as $k => $v)
                        {
                            if (strpos($k, 'pivot_')!==false)
                            {
                                $child = str_replace('pivot_', '', $k);
                                $pivot->$child = $v;
    
                                if (!in_array($k, $to_remove))
                                    $to_remove[] = $k;
                            }
                        }
                        $r->$pivot_name = $pivot;

                    }
                    else
                    {
                        $pivot = new DB;
                        foreach ($r->getOriginal() as $k => $v)
                        {
                            if (strpos($k, 'pivot_')!==false)
                            {
                                $child = str_replace('pivot_', '', $k);
                                $pivot->_setOriginalKey($child, $v);
                                $pivot->$child = $v;
    
                                if (!in_array($k, $to_remove))
                                    $to_remove[] = $k;
                            }
                            $pivot->setAppends(null);
                            $pivot->setRelations(null);
                            unset($pivot->_global_scopes);
                            $pivot->setQuery(null);
                        }
                        $r->setRelationAttribute($pivot_name, $pivot);
                    }

                }

            }

            elseif ($oneOfMany || 
            (in_array($relationship, array('hasOne', 'belongsTo', 'morphOne', 'morphTo', 'hasOneThrough'))))
            {
                $results = $columns!='*'
                    ? $res->where($key, $current->$primary)->keys($columns) 
                    : $res->where($key, $current->$primary);

                $results = $results->first();
            }
            
            elseif ((in_array($relationship, array('hasMany', 'hasManyThrough'))))
            {
                $results = $columns!='*'
                    ? $res->where($key, $current->$primary)->keys($columns) 
                    : $res->where($key, $current->$primary);

                //$results = $results;
            }

            if ($current instanceof Model)
                $current->setRelationAttribute($relation, $results);
            else
                $current->{$relation} = $results;
            
        }

        //dd($parent);

        if (count($to_remove)>0)
        {
            foreach ($parent->_collection as $item)
            {
                if ($item->$relation instanceof Collection)
                {
                    foreach ($item->$relation as $it)
                    {
                        foreach ($to_remove as $remove)
                        {
                            if ($it instanceof Model)
                            {
                                $it->unsetAttribute($remove);
                                $it->unsetOriginal($remove);
                            }
                            else
                            {
                                unset($it->$remove);
                            }
                        }
                    }
                }
                else
                {
                    foreach ($to_remove as $remove)
                    {
                        if ($item instanceof Model)
                        {
                            $item->unsetAttribute($remove);
                            $item->unsetOriginal($remove);
                        }
                        else
                        {
                            unset($item->$remove);
                        }
                    }
                }
            }
        }


        $parent->_loadedRelations[] = $relation;

        //dump($parent->_collection);
    }


}