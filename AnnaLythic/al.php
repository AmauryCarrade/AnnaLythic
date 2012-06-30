<?php
	header('Content-Type: text/plain');
	ini_set('html_errors', '0');

	if(file_exists('NotInstalled')) {
		echo 'alert|Please install AnnaLythics by running AnnaLythic/install.php';
		exit;
	}

	$settings = include('settings.php');

	$dump = json_decode($_POST['dump']);
	if(json_last_error() != JSON_ERROR_NONE) {
		echo 'An error occurred when parsing JSON: ';
		switch (json_last_error()) {
	        case JSON_ERROR_DEPTH:
	            echo 'Maximum depth exceeded (JSON_ERROR_DEPTH)';
	        break;
	        case JSON_ERROR_STATE_MISMATCH:
	            echo 'State mismatch or underflow (JSON_ERROR_STATE_MISMATCH)';
	        break;
	        case JSON_ERROR_CTRL_CHAR:
	            echo 'Error when controlling characters (JSON_ERROR_CTRL_CHAR)';
	        break;
	        case JSON_ERROR_SYNTAX:
	            echo 'Syntax error (JSON_ERROR_SYNTAX)';
	        break;
	        case JSON_ERROR_UTF8:
	            echo 'Encoding error (UTF-8 is needed) (JSON_ERROR_UTF8)';
	        break;
	        default:
	            echo 'Unknow error #' . json_last_error() . '... FUUUUU';
	        break;
	    }
	    exit;
	}


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
		foreach($settings['plugins'] as $pluginToDetect) {  // Items in array: see settings.php.
			if(!isset($pluginToDetect[1])) $pluginToDetect[1] = NULL;
			if(!isset($pluginToDetect[2]) || !is_bool($pluginToDetect[2])) $pluginToDetect[2] = false;
			testPlugin($plugin, $pluginToDetect[0], $pluginToDetect[1], $pluginToDetect[2]);
		}
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
		$geoloc['country']['name'] = 'Undefined (Local access)';
		$geoloc['city']['name']    = 'Undefined (Local access)';
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

	// Cookies

	$cookieEnabled = (bool) $dump->browser->cookie;



	$userAgent = $_SERVER['HTTP_USER_AGENT'];
	
	// Operating System
	
	$OS = array(
		'name'    => 'Undefined',
		'version' => '0.0'
	);
	
	function testOS($OSName, $regexName = NULL, $regexVersion = NULL, $dotsRepresentation = NULL) {
		global $OS;
		global $userAgent;

		if($OS['name'] != 'Undefined') return;
		if($regexName == NULL) 
			$regexName = '#' . $OSName . '#';

		if(preg_match($regexName, $userAgent)) {
			$OS['name'] = $OSName;
			if($regexVersion != NULL) {
				if(strpos($regexVersion, '#') !== false) {
					$matches = array();
					if(preg_match($regexVersion, $userAgent, $matches)) {
						$OS['version'] = $matches[1];
						if($dotsRepresentation != NULL) {
							$OS['version'] = str_replace($dotsRepresentation, '.', $OS['version']);
						}
					}
				}
				else {
					$OS['version'] = $regexVersion;
				}
			}
		}
	}

	foreach($settings['os'] as $OSToDetect) { // Items in array: see settings.php.
		if(!isset($OSToDetect[1])) $OSToDetect[1] = NULL;
		if(!isset($OSToDetect[2])) $OSToDetect[2] = NULL;
		if(!isset($OSToDetect[3])) $OSToDetect[3] = NULL;
		testOS($OSToDetect[0], $OSToDetect[1], $OSToDetect[2], $OSToDetect[3]);
	}




	// Database connexion
	try {
		$pdo = new PDO($settings['db']['type'] . ':host=' . $settings['db']['host'] . ';dbname=' . $settings['db']['base'], $settings['db']['user'], $settings['db']['pass']);
	}
	catch (PDOException $e) {
		echo "\n" . '[AnnaLythic] An error occurred about PDO. We are unable to connect the database. Error #' . $e->getCode() . ': ' . $e->getMessage();
		exit;
	}
