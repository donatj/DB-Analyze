<?php

use Connectors\MySql;

error_reporting(E_ALL ^ E_NOTICE);

include('functions/global.php');

spl_autoload_register(function($class){
	require('classes/' . str_replace('\\', '/', $class) . '.php');
});

if(file_exists('db_analyze.ini')) {
	$settings = parse_ini_file('db_analyze.ini');
}

foreach( $settings as $setting => $value ) {
	define('SETTING_' . strtoupper($setting), $value );
}


class conn extends MySql {
	protected static $_host     = SETTING_HOST;
	protected static $_user     = SETTING_USER;
	protected static $_password = SETTING_PASSWORD;
	protected static $_database = SETTING_DATABASE;
	protected static $_charset  = 'utf8';
}

