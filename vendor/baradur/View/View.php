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
        foreach ($errors as $key => $val) {
            $_SESSION['errors'][$key] = $val;
		}

        return $this;
    }
	
	# Returns an asset's full address
	# _ASSETS is defined in Globals.php
	public static function getAsset($asset)
	{
		return config('app.url').'/'.$asset;
	}

	public static function share($key, $value)
	{
		self::$shared[$key] = $value; 
	}

	public static function composer($templates, $callback)
	{
		if (!is_array($templates)) {
			$templates = array($templates);
		}

		foreach ($templates as $template) {
			self::$composers[$template] = $callback;
		}
	}

	# Loads the template file
	public static function loadTemplate($file, $args=array())
	{
		return self::renderTemplate($file, $args);
	}

	public static function renderTemplate($file, $args, $return_blade=false)
	{
		list($views, $file) = Blade::__findTemplate($file);

		$arguments = array('app_name' => config('app.name'));

		if (isset($args)) {
			foreach ($args as $key => $val) {
				$arguments[$key] = $val;
			}
		}

		if (count(self::$shared)>0) {
			$arguments = array_merge($arguments, self::$shared);
		}

		$composer = isset(self::$composers[$file])? self::$composers[$file] : null;

		if (strpos($file, '.')>0 && !$composer) {
			$arr = explode('.', $file);
			$first = $arr[0];

			if (isset(self::$composers[$first.'.*'])) {
				$composer = self::$composers[$first.'.*'];
			} else {
				$last = array_pop($arr); 
				$temp = str_replace($last, '*', $file);

				if (isset(self::$composers[$temp]))
					$composer = self::$composers[$temp];
			}
		}

		if ($composer) {
			if (is_closure($composer)) {
				list($class, $method, $params) = getCallbackFromString($composer);
				$params[0] = new View();
				call_user_func_array(array($class, $method), $params);
			} else {
				$class = new $composer;
				$class->composer(new View);
			}
		}

		if (isset($_SESSION['messages'])) {
			$arguments = array_merge($arguments, $_SESSION['messages']);
		}

		global $errors;

		if (isset($_SESSION['errors'])) {
			$errors = new MessageBag($_SESSION['errors']);
		}

		if (isset($errors)) {
			$arguments['errors'] = $errors;
		}

		//$app->arguments = $args;

		$cache = _DIR_.'storage/framework/views';
		$blade = new BladeOne($views, $cache);
		
		$result = self::$autoremove 
			? $blade->runInternal($file, $arguments, true) 
			: $blade->run($file, $arguments);

		unset($_SESSION['messages']);
		unset($_SESSION['errors']);

		$blade->html = $result;
		
		return $return_blade? $blade : $result;
	}

	public static function showErrorTemplate($code, $message=null)
    {
		# Since this view is for end users
		# all error codes not in the list
		# will be changed into error 500 
		if (!in_array($code, array(400, 401, 402, 403, 404, 419, 429, 500, 503))) {
            $code = 500;
			$message = __('Server Error');
        }

		# 404 must show "not found"
		# This prevent, for example, "model not found"
		if ($code==404) {
			$message = __('Not Found');
		}

		# If there's a blade file inside resources
		# folder, then use it as template
        if (Blade::__findTemplate('errors.'.$code)) {
			
            $result = view(
                'errors.'.$code,
                array(
                    'title' => $message,
                    'code' => $code,
                    'message' => $message
                )
            );

            return CoreLoader::processResponse(response($result, $code));
        }

		# If there isn't a blade file inside resources
		# folder, then use default framework template
		$blade = new BladeOne(_DIR_.'vendor/baradur/Exceptions/views', _DIR_.'storage/framework/views');

        $result = $blade->runInternal(
            'error', 
			array(
                'title' => $message,
                'code' => $code,
                'message' => $message
            ),
            true
        );
    
        return CoreLoader::processResponse(response($result, $code));

    } 

}
