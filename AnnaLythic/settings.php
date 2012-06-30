<?php
	/****************************************************************
	 * AnnaLythic -- GLOBAL SETTINGS
	 */

	$settings = array();

	// Database connexion
	$settings['db']['type']   = 'mysql';       // Values: PDO engine (mysql, postgresql, sqlite...) (/!\ Only PDO.)
	$settings['db']['host']   = 'localhost';   // Where is hosted the database? (SQLite: tip here the path to the file.)
	$settings['db']['user']   = 'root';        // Username to use to connect the database.
	$settings['db']['pass']   = '';            // Password to use to connect the database.
	$settings['db']['base']   = 'AnnaLythic';  // The base to be used.
	$settings['db']['prefix'] = 'al_';		   // If this is not null, all tables will be prefixed.

	// Path to the GeoIP database (frequent file name: GeoLiteCity.dat).
	$settings['geoip']['db']  = 'lib/GeoIP/GeoLiteCity.dat';

	// Operating System detection
	# === Usage
	# $settings['os'][] = array('OS Name', // Displayed in the dashboard
	#							'OS Regex (may be NULL if it is #OS Name#) for detection in User Agent', 
	#							'Regex for version ($1 must contains the version number) for detection in concerned User 
	#							 Agent',
	#							'If dots are represented as an other character (example: "_" for Mac OS X), tip this 
	#							 character here; it will be replaced by a dot.'
	#					  );
	#
	# In case of changes, please publish your changes on Github (github.com/Bubbendorf/AnnaLythic)! Thanks :)
	
	$settings['os'][] = array('iPad', NULL, '#iPad; U; CPU OS ([0-9_]{3-})#', '_'); // Check this
	$settings['os'][] = array('iPhone', NULL, '#iPhone OS ([0-9_]{3-})#', '_');
	$settings['os'][] = array('iPod Touch', '#iPod#', '#iPhone OS ([0-9_]{3-})#', '_');
	$settings['os'][] = array('Android', NULL, '#Android ([0-9.]{3-})#');
	$settings['os'][] = array('BlackBerry', NULL, '#Version/([0-9.]{5})#');
	
	$settings['os'][] = array('Windows 8', '#Windows NT 6.2#', '8');
	$settings['os'][] = array('Windows 7', '#Windows NT 6.1#', '7');
	$settings['os'][] = array('Windows Vista', '#Windows NT 6.0#', 'Vista');
	$settings['os'][] = array('Windows XP', '#Windows NT 5.1#', 'XP');
	$settings['os'][] = array('Windows Server 2003', '#Windows NT 5.2#', '2003');
	$settings['os'][] = array('Windows 2000', '#Windows NT 5.0#', '2000');
	$settings['os'][] = array('Windows NT', '#Windows NT#', 'NT');
	$settings['os'][] = array('Windows 98', '#Windows 98#', '98');
	$settings['os'][] = array('Windows 95', '#Windows 95#', '95');
	$settings['os'][] = array('Windows 3.1', '#Windows 3.1#', '3.1');
	$settings['os'][] = array('Unknow Windows', '#Windows#');

	$settings['os'][] = array('FreeBSD');
	$settings['os'][] = array('Mac OS X', NULL, '#Mac OS X ([0-9_]{3-})#', '_');
	$settings['os'][] = array('Ubuntu Linux', '#Ubuntu#i', '#Ubuntu/([0-9.]{4-5})#');
	$settings['os'][] = array('Fedora');
	$settings['os'][] = array('Gentoo', '#gentoo#i');
	$settings['os'][] = array('Kanotix', '#kanotix#i');
	$settings['os'][] = array('Open Solaris', '#SunOS#');
	$settings['os'][] = array('Unknow Linux', '#Linux#');

	$settings['os'][] = array('Irix', '#IRIX#');
	$settings['os'][] = array('BeOS');
	$settings['os'][] = array('SymbianOS', NULL, '#SymbianOS/([0-9.]{3})#');



	/****************************************************************
	 * /!\ DO NOT FORGET TO CONFIGURE THE PATH TO AnnaLythic/al.php IN AnnaLythic/al.js FILE (line 9).
	 */

	/****************************************************************
	 * Configuration is done. You can close this file ;) .
	 */

	return $settings;