<?php

error_reporting(E_ALL ^ E_NOTICE);

include('functions/global.php');
include('classes/Database.php');

if(file_exists('db_analyze.ini')) {
	$settings = parse_ini_file('db_analyze.ini');
}

foreach( $settings as $setting => $value ) {
	define('SETTING_' . strtoupper($setting), $value );
}


class db extends Database {
	protected static $_host     = SETTING_HOST;
	protected static $_user     = SETTING_USER;
	protected static $_password = SETTING_PASSWORD;
	protected static $_database = SETTING_DATABASE;
	protected static $_charset  = 'utf8';
}

