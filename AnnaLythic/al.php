<?php
	header('Content-Type: text/plain');

	if(file_exists('NotInstalled')) {
		echo 'alert|Please install AnnaLythics by running AnnaLythic/install.php';
		exit;
	}

	$settings = include('settings.php');

	// Database connexion
	try {
		$pdo = new PDO($settings['db']['type'] . ':host=' . $settings['db']['host'] . ';dbname=' . $settings['db']['base'], $settings['db']['user'], $settings['db']['pass']);
	}
	catch (PDOException $e) {
		echo '[AnnaLythics] An error occurred about PDO. We are unable to connect the database. Error #' . $e->getCode() . ': ' . $e->getMessage();
	}

	$dump = json_decode($_POST['dump']);

	//print_r($dump);

	// Plugins

	$pluginsEnabled = array();
	function testPlugin($name, $pluginText, $nameInArray = NULL, $equal = false) {
		global $pluginsEnabled;
		if($nameInArray == NULL) $nameInArray = $pluginText;
		if(isset($pluginsEnabled[$nameInArray]) AND $pluginsEnabled[$nameInArray] === true) return;
		
		if(!$equal) $pluginsEnabled[$nameInArray] = strpos($name, $pluginText) !== false ? true : 'undefined';
		else 		$pluginsEnabled[$nameInArray] = $name == $pluginText ? true : 'undefined';
	}

	foreach($dump->browser->plugins as $id => $plugin) {
		testPlugin($plugin->name, 'QuickTime');
		testPlugin($plugin->name, 'Adobe Acrobat', 'PDF', true);
		testPlugin($plugin->name, 'Shockwave Flash', 'Flash', true);
		testPlugin($plugin->name, 'Google Earth Plugin', 'Google Earth', true);
		testPlugin($plugin->name, 'Java(TM)', 'Java');
		testPlugin($plugin->name, 'Silverlight Plug-In', 'Silverlight', true);
		testPlugin($plugin->name, 'VLC Web Plugin', 'VLC', true);
	}
	foreach($pluginsEnabled as $name => $enabled) {
		if($enabled === 'undefined') {
			$pluginsEnabled[$name] = false;
		}
	}
	

	$ipAddress = $_SERVER['REMOTE_ADDR'];

	// Geolocation

	require_once('lib/GeoIP/geoipcity.inc');
	$geoIp = geoip_open($settings['geoip']['db'], GEOIP_STANDARD);
	$geoloc = array();
	if(in_array($ipAddress, array('127.0.0.1', '::1'))) {
		$geoloc['country']['code'] = '??';
		$geoloc['country']['name'] = 'Undefined';
		$geoloc['city']['name']    = 'Undefined';
		$geoloc['latitude']        = 0;
		$geoloc['longitude']       = 0;
	}
	else {
		$record = geoip_record_by_addr($geoIp, $ipAddress);
		$geoloc['country']['code'] = $record->country_code;
		$geoloc['country']['name'] = $record->country_name;
		$geoloc['city']['name']    = $record->city;
		$geoloc['latitude']        = $record->latitude;
		$geoloc['longitude']       = $record->longitude;
	}
