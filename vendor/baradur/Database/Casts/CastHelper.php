<?php

class CastHelper
{

    public static function parseCastType($type, $val, $for_array=false, $data=null, $model=null, $key=null)
    {
        global $_class_list;
        list($type, $format) = explode(':', $type);

        if (array_key_exists($type, $_class_list)) {
            $model->fill($data);
            $class = new $type;
            return $class->get($model, $key, $val, $data);
        } 

        if (in_array($type, array('timestamp', 'date', 'datetime')) && $val) {
            if ($for_array) {
                return Carbon::parse($val)->settings(array('toStringFormat'=>$format))->__toString();
            }

            return Carbon::parse($val)->settings(array('toStringFormat'=>$format));
        }

        if ($type == 'boolean') {
            return Str::of($val)->toBoolean();
        }
        
        if ($type == 'array') {
            return unserialize($val);
        }

        if ($type == 'integer') {
            return intval($val);
        }

        return $val;

    }

    public static function parseCastTypeBack($type, $val, $data=null, $model=null, $key=null)
    {
        global $_class_list;

        $arr = explode(':', $type);
        $type = array_shift($arr);

        if (array_key_exists($type, $_class_list)) {
            $model->fill($data);
            $class = new $type;
            return $class->set($model, $key, $val, $data);
        } 

        if (in_array($type, array('timestamp', 'date', 'datetime'))) {
            return Carbon::parse($val)->toDateTimeString();
        }

        if ($type == 'boolean') {
            return $val==true? 1 : 0;
        }

        if ($type == 'array') {
            return serialize($val);
        }

        return $val;
    }

    public static function processCasts($item, $model, $for_array=false)
    {
        $casts = $model->getCasts();
        $cast_keys = array_keys($casts);

        foreach ($item as $key => $val) {
            $dates = array($model->getCreatedAtColumn(), $model->getUpdatedAtColumn());
            
            if ($model->usesSoftDeletes()) {
                $dates[] = $model->getDeletedAtColumn();
            }

            if (in_array($key, $cast_keys)) {
                $item[$key] = self::parseCastType($casts[$key], $val, $for_array, $item, $model, $key);
            } elseif (in_array($key, $dates) && $val) {
                if ($for_array) {
                    $item[$key] = $model->_getSerializedDate($val);
                } else {
                    $item[$key] = Carbon::parse($val);
                }
            }
        }

        return $item;
    }

    public static function processCastsBack($item, $model)
    {
        $casts = $model->getCasts();
        $cast_keys = array_keys($casts);

        foreach ($item as $key => $val) {
            if (in_array($key, $cast_keys) && $val) {
                $item[$key] = self::parseCastTypeBack($casts[$key], $val, $item, $model, $key);
            }
            elseif (in_array($key, array('deleted_at', $model->getCreatedAt(), $model->getUpdatedAt())) && $val) {
                $item[$key] = Carbon::parse($val)->toDateTimeString();
            }
        }

        return $item;
    }
}