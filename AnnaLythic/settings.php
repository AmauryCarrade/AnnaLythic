<?php
	/****************************************************************
	 * AnnaLythics -- GLOBAL SETTINGS
	 */

	$settings = array();

	// Database connexion
	$settings['db']['type']   = 'mysql';       // Values: PDO engine (mysql, postgresql, sqlite...)
	$settings['db']['host']   = 'localhost';
	$settings['db']['user']   = 'root';
	$settings['db']['pass']   = '';
	$settings['db']['base']   = 'AnnaLythic';
	$settings['db']['prefix'] = 'al_';

	// Path to the GeoIP database (frequent file name: GeoLiteCity.dat).
	$settings['geoip']['db']  = 'lib/GeoIP/GeoLiteCity.dat';
	

	/****************************************************************
	 * Configuration is done. You can close this file ;) .
	 */

	return $settings;