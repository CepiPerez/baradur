<?php

# This controller handles the view
# Uses BladeOne to so wa can use Laravel BLADE templates

Class View
{
	# Pagination
	public static $pagination;
	
	# Returns an asset's full address
	# _ASSETS is defined in Globals.php
	public static function getAsset($asset)
	{
		return env('HOME').'/'.$asset;
	}

	# Sets pagination
	public static function setPagination($val)
	{
		self::$pagination = $val;
	}

	# Gets pagination
	public static function pagination()
	{
		return self::$pagination;
	}


	# Loads the template file
	static function loadTemplate($file, $args=array())
	{
		global $app, $artisan;

		/* echo "VIEW PARAMS:";
		dd($args);
		echo "END VIEW PARAMS:"; */

		$file = str_replace('.', '/', $file);

		if (!file_exists(_DIR_.'/../../resources/views/'.$file.'.blade.php') && !isset($artisan))
			abort(404);

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

		if (isset($_SESSION['messages']))
			App::setSessionMessages($_SESSION['messages']);
			
		global $errors;
		if (isset($_SESSION['errors']))
			$errors = new MessageBag($_SESSION['errors']);

		if (isset($errors))
			$arguments['errors'] = $errors;

		//$app->arguments = $args;


		#include "BladeOne2.php";
		$views = _DIR_.(!isset($artisan)?'/../..':'').'/resources/views';
		$cache = _DIR_.(!isset($artisan)?'/../..':'').'/storage/framework/views';
		$blade = new BladeOne($views, $cache);

		//define("BLADEONE_MODE", env('APP_DEBUG')); // (optional) 1=forced (test),2=run fast (production), 0=automatic, default value.

		$result = $blade->run($file, $arguments);

		unset($_SESSION['messages']);
		unset($_SESSION['errors']);
		unset($_SESSION['old']);

		return $result;

	}


}
