<?php

error_reporting(E_ERROR);

$publicPath = str_replace('vendor/baradur/Console', '', dirname(__FILE__));

$_GET['ruta'] = ltrim($_SERVER['REQUEST_URI'], '/');

require_once $publicPath.'/public/index.php';
