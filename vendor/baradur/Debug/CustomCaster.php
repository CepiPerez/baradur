<?php

/**
 * @method static CustomCaster for(string $class_name)
*/

class CustomCaster
{    
	public $operations = array();

    public static $defaultCasters = array();
	
	public static function instanceFor($class_name)
    {
		$caster = new CustomCaster();
		
        self::$defaultCasters[$class_name] = $caster;
		
		return $caster;
	}
	
	public function reorder($rules)
	{
		$this->operations['reorder'] = is_array($rules)? $rules : array($rules);;
		
		return $this;
	}
	
	public function filter($filter = null)
	{
		$this->operations['filter'] = $filter;
		
		return $this;
	}
	
	public function dynamic($key, $callback)
	{
		$this->operations['dymanic'] = array($key => $callback);
		
		return $this;
	}
	
	public function virtual($key, $callback)
	{
		$this->operations['virtual'][$key] = $callback;
		
		return $this;
	}
	
	public function only($keys)
    {
		$this->operations['only'] = is_array($keys)? $keys : array($keys);
		
		return $this;
	}
	
	public function except($keys)
	{
		$this->operations['except'] = is_array($keys)? $keys : array($keys);
		
		return $this;
	}
}