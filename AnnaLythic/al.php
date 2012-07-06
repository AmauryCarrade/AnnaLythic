<?php
	header('Content-Type: text/plain');
	ini_set('html_errors', '0');
	session_start();

	if(file_exists('NotInstalled')) {
		echo 'alert|Please install AnnaLythics by running AnnaLythic/install.php';
		exit;
	}

	$settings = include('settings.php');

	if($settings['debug']) {
		echo '[AnnaLythic] Welcome. Debug mode is enabled.';
	}

	$dump = json_decode($_POST['dump']);
	if(json_last_error() != JSON_ERROR_NONE) {
		if($settings['debug']) {
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
		}
	    exit;
	}

	// Check filters

	if(isset($settings['filters']['ip'])) {
		$exit = false;
		foreach($settings['filters']['ip'] as $ip) {
			if(strpos($ip, '#') !== false) {
				if(preg_match($ip, $_SERVER['REMOTE_ADDR'])) {
					$exit = true;
					break;
				}
			}
			else {
				if($ip == $_SERVER['REMOTE_ADDR']) {
					$exit = true;
					break;
				}
			}
		}
		if($exit) {
			if($settings['debug'])
				echo '[AnnaLythic] Your IP (' .  $_SERVER['REMOTE_ADDR'] . ') is excluded from AnnaLythic. You have not been traced.';
			exit;
		}
	}

	if(isset($settings['filters']['page'])) {
		$exit = false;
		foreach($settings['filters']['page'] as $page) {
			if(strpos($page, '#') !== false) {
				if(preg_match($page, $dump->url)) {
					$exit = true;
					break;
				}
			}
			else {
				if($page == $dump->url) {
					$exit = true;
					break;
				}
			}
		}
		if($exit) {
			if($settings['debug']) 
				echo '[AnnaLythic] This page (' .  $dump->url . ') is excluded from AnnaLythic. You have not been traced.';
			exit;
		}
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

	$geoloc = array();
	if($settings['geoip']['enabled']) {
		require_once('lib/GeoIP/geoipcity.inc');
		$geoIp = geoip_open($settings['geoip']['db'], GEOIP_STANDARD);
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



	// Browser

	$Browser = array(
		'name'    => 'Undefined',
		'version' => '0.0',
		'type'    => 'Undefined'
	);

	function testBrowser($BrowserName, $regexName = NULL, $regexVersion = NULL, $type = 'user') {
		global $Browser;
		global $userAgent;

		if($Browser['name'] != 'Undefined') return;
		if($regexName == NULL) 
			$regexName = '#' . $BrowserName . '#';

		if(preg_match($regexName, $userAgent)) {
			$Browser['name'] = $BrowserName;
			if($regexVersion != NULL) {
				if(is_string($regexVersion)) {
					$matches = array();
					if(preg_match($regexVersion, $userAgent, $matches)) {
						$Browser['version'] = $matches[1];
					}
				}
				else if(is_callable($regexVersion)) {
					$Browser['version'] = $regexVersion($userAgent);
				}
			}
		}
		$Browser['type'] = in_array($type, array('user', 'bot')) ? $type : 'user';
	}

	foreach($settings['browsers'] as $BrowserToDetect) { // Items in array: see settings.php.
		if(!isset($BrowserToDetect[1])) $BrowserToDetect[1] = NULL;
		if(!isset($BrowserToDetect[2])) $BrowserToDetect[2] = NULL;
		if(!isset($BrowserToDetect[3])) $BrowserToDetect[3] = 'user';
		testBrowser($BrowserToDetect[0], $BrowserToDetect[1], $BrowserToDetect[2], $BrowserToDetect[3]);
	}



	// Screen

	$ScreenDefinition = array(
		'width' => $dump->screen->width,
		'height' => $dump->screen->height,
		'text' => $dump->screen->width . 'x' . $dump->screen->height
	);

	$ColorDepth = (int) $dump->screen->colorDepth;

	$FontSmoothing = (bool) $dump->screen->fontSmoothing;



	// Referrer & search engine

	$referrer = (string) $dump->referrer;
	$Source = array(
		'url'	   => $referrer,
		'type'     => NULL, // direct, internal, website or searchEngine.
		'name'     => NULL, // If the source is a search engine, his name.
		'keywords' => NULL
	);

	if($referrer == NULL) {
		$Source['type'] = 'direct';
	}
	else if(parse_url($referrer, PHP_URL_HOST) == parse_url($dump->url, PHP_URL_HOST)) {
		$Source['type'] = 'internal';
	}
	else {
		foreach($settings['searchEngines'] as $searchEngineToDetect) {
			if(!isset($searchEngineToDetect[2])) $searchEngineToDetect[2] = NULL;
			if(preg_match($searchEngineToDetect[1], $referrer)) {
				$Source['type'] = 'searchEngine';
				$Source['name'] = $searchEngineToDetect[0];
				if($searchEngineToDetect[2] != NULL) {
					$urlQuery = parse_url($referrer, PHP_URL_QUERY);
					$query = array();
					parse_str($urlQuery, $query);
					$Source['keywords'] = $query[$searchEngineToDetect[2]];
				}
			}
		}
		if($Source['type'] == NULL) {
			$Source['type'] = 'website';
		}
	}






	$records = array(
		'browser' => $Browser,
		'os'      => $OS,
		'plugins' => $pluginsEnabled,
		'screen'  => array(
			'definition' => $ScreenDefinition,
			'colorDepth' => $ColorDepth,
			'fontSmoothing' => $FontSmoothing
		),
		'source' => $Source
	);

	if($settings['geoip']['enabled']) {
		$records['geolocation'] = $geoloc;
	}

	if($settings['debug']) {
		echo "\n\n" . 'Records:' . "\n\n";
		print_r($records);
	}


	// Database connexion

	try {
		$pdo = new PDO($settings['db']['type'] . ':host=' . $settings['db']['host'] . ';dbname=' . $settings['db']['base'], $settings['db']['user'], $settings['db']['pass']);
	}
	catch (PDOException $e) {
		if($settings['debug'])
			echo "\n" . '[AnnaLythic] An error occurred about PDO. We are unable to connect the database. Error #' . $e->getCode() . ': ' . $e->getMessage();
		exit;
	}



	// Session management

	# Get session for an easier access
	$session = $_SESSION[$settings['session']['name']];
	$datetime = new \Datetime();

	# @see settings.php:23
	if(isset($session['history']) && abs($datetime->getTimestamp() - $session['history'][count($session['history']) - 1]['time']->getTimestamp()) > $settings['session']['durationBetweenTwoSessions']) {
		$session = array();
	}

	# Saving current page in session history
	$session['history'][] = array(
		'page' => $dump->url,
		'time' => $datetime
	);

	# Visit duration
	$session['duration'] = abs($datetime->getTimestamp() - $session['history'][0]['time']->getTimestamp());

	# Save the session
	$_SESSION[$settings['session']['name']] = $session;


	
	// Data storage
	$tableVisits = $settings['db']['prefix'] . 'visits';
	$tableSessions = $settings['db']['prefix'] . 'sessions';
	try {
		function lastInsertId($pdo, $table, $idField) {
			if($pdo->lastInsertId() != NULL) {
				return $pdo->lastInsertId();
			}
			else {
				$request = $pdo->prepare("SELECT $idField FROM $table ORDER BY $idField DESC LIMIT 1");
				$request->execute();
				$result = $request->fetch();
				return $result[$idField];
			}
		}

		# Visit
		$sql = 'INSERT INTO :table (ip, url, datetime, records)
				VALUES (:ip, :url, NOW(), :records)';

		$sql = str_replace(':table', $tableVisits, $sql);
		$request = $pdo->prepare($sql);
		$request->execute(array(
			':ip'      => ip2long($ipAddress),
			':url'     => $dump->url,	
			':records' => serialize($records)
		));

		# Session
		$_SESSION[$settings['session']['name']]['history'][count($session['history']) - 1]['visit_id'] = lastInsertId($pdo, $tableVisits, 'visit_id');

		if($settings['debug']) {
			echo "\n\n" . 'Session:' . "\n\n";
			print_r($_SESSION[$settings['session']['name']]);
		}

		$sql;
		if(!isset($session['id'])) {
			$sql = 'INSERT INTO :table (ip, history, pagesCount, duration, entrance)
					VALUES (:ip, :history, :pagesCount, :duration, :entrance)';
		}
		else {
			$sql = 'UPDATE :table SET ip = :ip,
									  history = :history,
									  pagesCount = :pagesCount,
									  duration = :duration
					WHERE session_id = :session_id';
		}

		$sql = str_replace(':table', $tableSessions, $sql);

		$ip = ip2long($ipAddress);
		$history = serialize($session['history']);
		$pagesCount = count($session['history']);

		$request = $pdo->prepare($sql);
		$request->bindParam(':ip', $ip, PDO::PARAM_INT);
		$request->bindParam(':history', $history, PDO::PARAM_STR);
		$request->bindParam(':pagesCount', $pagesCount, PDO::PARAM_INT);
		$request->bindParam(':duration', $session['duration'], PDO::PARAM_INT);
		if(isset($session['id'])) {
			$request->bindParam(':session_id', $session['id'], PDO::PARAM_INT);
		}
		else {
			$request->bindParam(':entrance', $session['history'][0]['visit_id'], PDO::PARAM_STR);
		}
		$request->execute();
		if(!isset($session['id'])) {
			$_SESSION[$settings['session']['name']]['id'] = lastInsertId($pdo, $tableSessions, 'session_id');
		}
	}
	catch (PDOException $e) {
		if($settings['debug'])
			echo "\n" . '[AnnaLythic] An error occurred about PDO. We are unable to save tracking data. Error #' . $e->getCode() . ': ' . $e->getMessage();
		exit;
	}
	catch (Exception $e) {
		if($settings['debug'])
			echo "\n" . '[AnnaLythic] An error occurred. Error #' . $e->getCode() . ': ' . $e->getMessage();
		exit;
	}
