<?php

class CastHelper
{

    public static function parseCastType($type, $val, $for_array=false)
    {
        list($type, $format) = explode(':', $type);

        if (in_array($type, array('timestamp', 'date', 'datetime')) && $val)
        {
            if ($for_array)
                return Carbon::parse($val)->settings(array('toStringFormat'=>$format))->__toString();

            return Carbon::parse($val)->settings(array('toStringFormat'=>$format));
        }

        if ($type == 'boolean')
            return Str::of($val)->toBoolean();
        
        if ($type == 'array')
            return unserialize($val);

        if ($type == 'integer')
            return intval($val);

        return $val;

    }

    public static function parseCastTypeBack($type, $val)
    {
        $arr = explode(':', $type);
        $type = array_shift($arr);

        if (in_array($type, array('timestamp', 'date', 'datetime')))
        {
            return Carbon::parse($val)->toDateTimeString();
        }

        if ($type == 'boolean')
            return $val==true? 1 : 0;

        if ($type == 'array')
            return serialize($val);

        return $val;

    }

    public static function processCasts($item, $model, $for_array=false)
    {
        $casts = $model->getCasts();
        $cast_keys = array_keys($casts);

        foreach ($item as $key => $val)
        {
            if (in_array($key, $cast_keys))
            {
                $item[$key] = self::parseCastType($casts[$key], $val, $for_array);
            }
            elseif (in_array($key, array('deleted_at', $model->getCreatedAt(), $model->getUpdatedAt())) && $val)
            {
                if ($for_array)
                    $item[$key] = $model->_getSerializedDate($val);
                else
                    $item[$key] = Carbon::parse($val);
            }
        }
        return $item;
    }

    public static function processCastsBack($item, $model)
    {
        $casts = $model->getCasts();
        $cast_keys = array_keys($casts);

        foreach ($item as $key => $val)
        {
            if (in_array($key, $cast_keys) && $val)
            {
                $item[$key] = self::parseCastTypeBack($casts[$key], $val);
            }
            elseif (in_array($key, array('deleted_at', $model->getCreatedAt(), $model->getUpdatedAt())) && $val)
            {
                $item[$key] = Carbon::parse($val)->toDateTimeString();
            }

        }

        return $item;
    }
}