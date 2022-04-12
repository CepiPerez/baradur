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
		return HOME.'/'._ASSETS.'/'.$asset;
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

	# Creates necessary cache folders
	# IMPORTANT: resources/_system folder should have full access (777)
	private static function checkFolders()
    {
		$perror = false;
		if ( !file_exists(__DIR__.'/../../../resources/_system/cache') )
		{
			if (!mkdir('/../../../resources/_system/cache', 0777))
			$perror = true;
		}
		if ($perror) 
		{
			echo "Error trying to create cache folders<br>".
			"Plase, give 777 permission to <b>resources/_system</b><br>";
			die();
		}

	}
	

	# Loads the template file
	static function loadTemplate($file, $args=array())
	{
		global $app;

		if (!file_exists(__DIR__.'/../../../resources/views/'.$file.'.blade.php'))
			abort(404);

		self::checkFolders();

		$arguments = array(
			'app_name' => APP_NAME
		);

		if (isset($_SESSION['old'])) {
			$old = new stdClass;
			foreach ($_SESSION['old'] as $key => $val)
				$old->$key = $val;
			$arguments['old'] = $old;
		}

		if (isset($_SESSION['messages']))
			App::setSessionMessages($_SESSION['messages']);

		if (isset($_SESSION['errors']))
			App::setSessionErrors($_SESSION['errors']);


		foreach ($args as $key => $val)
			$arguments[$key] = $val;

		global $errors;
		if (isset($errors))
			$arguments['errors'] = $errors;

		$app->arguments = $args;


		#include "BladeOne2.php";
		$views = __DIR__.'/../../../resources/views';
		$cache = __DIR__.'/../../../resources/_system/cache';
		$blade = new BladeOne($views, $cache);
		define("BLADEONE_MODE", 1); // (optional) 1=forced (test),2=run fast (production), 0=automatic, default value.

		$result = $blade->run($file, $arguments);

		unset($_SESSION['messages']);
		unset($_SESSION['errors']);
		unset($_SESSION['old']);

		return $result;

	}


}
