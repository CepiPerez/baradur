<?php

# This controller handles the view
# Uses BladeOne to so wa can use Laravel BLADE templates

Class View
{
	public static $shared = array();
	public static $composers = array();

	public static $autoremove = false;


	public function with($key, $value)
    {
        $_SESSION['messages'][$key] = $value;
        return $this;
    }

	public function withErrors($errors)
    {
        foreach ($errors as $key => $val)
            $_SESSION['errors'][$key] = $val;

        return $this;
    }

	
	# Returns an asset's full address
	# _ASSETS is defined in Globals.php
	public static function getAsset($asset)
	{
		return env('HOME').'/'.$asset;
	}

	

	public static function share($key, $value)
	{
		self::$shared[$key] = $value; 
	}


	public static function composer($templates, $callback)
	{
		if (!is_array($templates)) 
			$templates = array($templates);

		foreach ($templates as $template)
			self::$composers[$template] = $callback;
	}


	# Loads the template file
	public static function loadTemplate($file, $args=array())
	{
		list($views, $file) = Blade::__findTemplate($file);

		$arguments = array(
			'app_name' => env('APP_NAME')
		);

		if (isset($_SESSION['old'])) {
			$old = new stdClass;
			foreach ($_SESSION['old'] as $key => $val)
				$old->$key = $val;
			$arguments['old'] = $old;
		}

		
		if (isset($args))
		{
			foreach ($args as $key => $val)
			$arguments[$key] = $val;
		}

		if (count(self::$shared)>0)
		{
			$arguments = array_merge($arguments, self::$shared);
		}

		$composer = isset(self::$composers[$file])? self::$composers[$file] : null;
		if (strpos($file, '.')>0 && !$composer)
		{
			$arr = explode('.', $file);
			$first = $arr[0];

			if (isset(self::$composers[$first.'.*']))
				$composer = self::$composers[$first.'.*'];

			else {
				$last = array_pop($arr); 
				$temp = str_replace($last, '*', $file);

				if (isset(self::$composers[$temp]))
					$composer = self::$composers[$temp];
			}
		}

		if ($composer)
		{
			if (strpos($composer, '@')>0)
			{
				list($class, $method, $params) = getCallbackFromString($composer);
				array_shift($params);
				call_user_func_array(array($class, $method), array_merge(array(new View()), $params));
			}
			else
			{
				$class = new $composer;
				$class->composer(new View);
			}

		}

		if (isset($_SESSION['messages']))
		{
			//App::setSessionMessages($_SESSION['messages']);
			$arguments = array_merge($arguments, $_SESSION['messages']);
		}

			
		global $errors;
		if (isset($_SESSION['errors']))
			$errors = new MessageBag($_SESSION['errors']);

		if (isset($errors))
			$arguments['errors'] = $errors;

		//$app->arguments = $args;

		$cache = _DIR_.'storage/framework/views';
		$blade = new BladeOne($views, $cache);

		$result = self::$autoremove ? 
			$blade->runInternal($file, $arguments, true) : $blade->run($file, $arguments);

		self::$autoremove = false;

		unset($_SESSION['messages']);
		unset($_SESSION['errors']);
		unset($_SESSION['old']);

		return $result;

	}


}
