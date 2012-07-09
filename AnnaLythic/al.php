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
			$geoloc['city']['name']    = 'Undefined (Local access)';
			$geoloc['latitude']        = 0;
			$geoloc['longitude']       = 0;
		}
		else {
			$record = geoip_record_by_addr($geoIp, $ipAddress);
			$geoloc['country']['code'] = $record->country_code;
			$geoloc['city']['name']    = $record->city;
			$geoloc['latitude']        = $record->latitude;
			$geoloc['longitude']       = $record->longitude;
		}
	}

	// Cookies

	$cookiesEnabled = (bool) $dump->browser->cookie;



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
		'cookies' => $cookiesEnabled,
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
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e) {
		if($settings['debug'])
			echo "\n" . '[AnnaLythic] An error occurred about PDO. We are unable to connect the database. Error #' . $e->getCode() . ': ' . $e->getMessage();
		exit;
	}


	// Session management
	# $_SESSION[$settings['session']['name']] = array(); exit;

	# Get session for an easier access
	$session = $_SESSION[$settings['session']['name']];
	$datetime = new \Datetime();

	# @see settings.php:23
	if(isset($session['lastVisit']) && abs($datetime->getTimestamp() - $session['lastVisit']->getTimestamp()) > $settings['session']['durationBetweenTwoSessions']) {
		session_regenerate_id();
		$session = array();
	}

	$session['lastVisit'] = new \Datetime();
	
	# Save the session
	$_SESSION[$settings['session']['name']] = $session;


	
	// Data storage
	$tableSessions   = $settings['db']['prefix'] . 'sessions';
	$tableNavigation = $settings['db']['prefix'] . 'navigation';
	$tableNavigators = $settings['db']['prefix'] . 'navigators';
	$tableOS         = $settings['db']['prefix'] . 'os';
	$tablePlaces     = $settings['db']['prefix'] . 'places';
	$tablePlugins    = $settings['db']['prefix'] . 'plugins';

	$PHPSESSID = session_id();

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

		# First step: update the navigation table.
		$sql = 'INSERT INTO :table (session_id, datetime, page)
								  VALUES (:PHPSESSID, NOW(), :url)';
		$sql = str_replace(':table', $tableNavigation, $sql);

		$request = $pdo->prepare($sql);
		$request->execute(array(
			':PHPSESSID' => $PHPSESSID,
			':url'       => $dump->url
		));


		# Next, we update or initiate the sessions table.
		if(isset($_SESSION[$settings['session']['name']]['saved']) && $_SESSION[$settings['session']['name']]['saved']) {
			$sql = 'UPDATE :table SET session_finish = NOW() WHERE session_id = :PHPSESSID';
			$sql = str_replace(':table', $tableSessions, $sql);
					
			$request = $pdo->prepare($sql);
			$request->execute(array(
				':PHPSESSID' => $PHPSESSID
			));
		}
		else {
			# Steps:
			# 1. Get the navigator's ID;
			# 2. Idem for the operating system;
			# 3. Idem for the geolocation;
			# 4. Save plugins in the database;
			# 5. Add a row in the session table.

			$navID = $OSID = $placeID = NULL;

			# STEP 1 - BROWSER
			$sql = 'SELECT navigator_id FROM :table 
						   WHERE     navigator_name    = :navName 
								 AND navigator_version = :navVersion
								 AND navigator_type    = :navType';
			$sql = str_replace(':table', $tableNavigators, $sql);

			$request = $pdo->prepare($sql);
			$request->execute(array(
				':navName'    => $records['browser']['name'],
				':navVersion' => $records['browser']['version'],
				':navType'    => $records['browser']['type']
			));

			$navID = $request->fetchColumn();

			if($navID === false) {
				$sql = 'INSERT INTO :table (navigator_name, navigator_version, navigator_type)
						VALUES (:navName, :navVersion, :navType)';
				$sql = str_replace(':table', $tableNavigators, $sql);

				$request = $pdo->prepare($sql);
				$request->execute(array(
					':navName'    => $records['browser']['name'],
					':navVersion' => $records['browser']['version'],
					':navType'    => $records['browser']['type']
				));

				$navID = lastInsertId($pdo, $tableNavigators, 'navigator_id');
			}

			# STEP 2 - OS
			$sql = 'SELECT os_id FROM :table 
					WHERE     os_name    = :OSName 
						  AND os_version = :OSVersion';
			$sql = str_replace(':table', $tableOS, $sql);

			$request = $pdo->prepare($sql);
			$request->execute(array(
				':OSName'     => $records['os']['name'],
				':OSVersion'  => $records['os']['version']
			));

			$OSID = $request->fetchColumn();

			if($OSID === false) {
				$sql = 'INSERT INTO :table (os_name, os_version)
						VALUES (:OSName, :OSVersion)';
				$sql = str_replace(':table', $tableOS, $sql);

				$request = $pdo->prepare($sql);
				$request->execute(array(
					':OSName'     => $records['os']['name'],
					':OSVersion'  => $records['os']['version']
				));

				$OSID = lastInsertId($pdo, $tableOS, 'os_id');
			}

			# STEP 3 - GEOLOCATION
			echo "\n\n" . '--- Geolocation ---';
			if($settings['geoip']['enabled'] && $records['geolocation']['country']['code'] != '??') {
				echo "\n\t GEOLOCATION ENABLED";
				$sql = 'SELECT place_id FROM :table 
						WHERE     place_country = :country
							  AND place_city    = :city';
				$sql = str_replace(':table', $tablePlaces, $sql);
				echo "\n Check: \n$sql\n\n";
				echo "SQL Vars:\n";
				print_r(array(
					':country' => $records['geolocation']['country']['code'],
					':city'    => $records['geolocation']['city']['name']
				));

				$request = $pdo->prepare($sql);
				$request->execute(array(
					':country' => $records['geolocation']['country']['code'],
					':city'    => $records['geolocation']['city']['name']
				));

				$placeID = $request->fetchColumn();

				if($placeID === false) {
					$sql = 'INSERT INTO :table (place_country, place_city)
							VALUES (:country, :city)';
					$sql = str_replace(':table', $tablePlaces, $sql);

					echo "\nSave: \n$sql\n\n";
					echo "SQL Vars:\n";
					print_r(array(
						':country' => $records['geolocation']['country']['code'],
						':city'    => $records['geolocation']['city']['name']
					));

					$request = $pdo->prepare($sql);
					$request->execute(array(
						':country' => $records['geolocation']['country']['code'],
						':city'    => $records['geolocation']['city']['name']
					));

					$placeID = lastInsertId($pdo, $tablePlaces, 'place_id');
					echo "\n\n\tPlace ID: $placeID";
				}
			}
			else {
				echo "\n\tNO GEOLOC";
				$placeID = 0;
			}

			echo "\n\n--- Plugins ---";
			# STEP 4 - PLUGINS
			$sql = 'INSERT INTO :table (session_id, plugin_name, plugin_enabled) VALUES ';
			$sql = str_replace(':table', $tablePlugins, $sql);

			$SQLVars = array(
				':PHPSESSID' => $PHPSESSID
			);

			$i = 0;
			foreach($records['plugins'] AS $plugin => $enabled) {
				$sql .= '(:PHPSESSID, :plugin' . $i . ', :enabled' . $i . '), ';
				$SQLVars[':plugin' . $i] = $plugin;
				$SQLVars[':enabled' . $i] = $enabled ? '1' : '0';
				$i++;
			}
			$sql = substr($sql, 0, -2);
			echo "\nSQL Query:\n$sql";
			echo "\nSQL Vars:\n";
			print_r($SQLVars);
			$request = $pdo->prepare($sql);
			$request->execute($SQLVars);

			# STEP 5 - SESSION ITSELF
			$sql = 'INSERT INTO :table (session_id, 
										session_start, session_finish, 
										ip,
										navigator, os, 
										geo_lat, geo_long, geo_place, 
										screen_definition, screen_color_depth, 
										screen_font_smoothing, 
										cookies, 
										source, source_type, 
										source_search_engine, source_keywords)
					VALUES (:PHPSESSID, 
							NOW(), NOW(),
							:ip, 
							:navID, :OSID, 
							:lat, :long, :placeID, 
							:screen_def, :screen_color, 
							:screen_font, 
							:cookies,
							:referrer, :source_type, 
							:source_search_engine, :source_keywords)';
			$sql = str_replace(':table', $tableSessions, $sql);

			$request = $pdo->prepare($sql);

			$lat = $settings['geoip']['enabled'] ? $records['geolocation']['latitude'] : NULL;
			$long = $settings['geoip']['enabled'] ? $records['geolocation']['longitude'] : NULL;
			$screenFont = $records['screen']['fontSmoothing'] ? '1' : '0';
			$cookies = $records['cookies'] ? '1' : '0';

			$request->bindParam(':PHPSESSID', $PHPSESSID, PDO::PARAM_STR);
			$request->bindParam(':ip', $ipAddress, PDO::PARAM_STR);
			$request->bindParam(':navID', $navID, PDO::PARAM_INT);
			$request->bindParam(':OSID', $OSID, PDO::PARAM_INT);
			$request->bindParam(':lat', $lat, PDO::PARAM_INT);
			$request->bindParam(':long', $long, PDO::PARAM_INT);
			$request->bindParam(':placeID', $placeID, PDO::PARAM_INT);
			$request->bindParam(':screen_def', $records['screen']['definition']['text'], PDO::PARAM_STR);
			$request->bindParam(':screen_color', $records['screen']['colorDepth'], PDO::PARAM_INT);
			$request->bindParam(':screen_font', $screenFont, PDO::PARAM_STR);
			$request->bindParam(':cookies', $cookies, PDO::PARAM_STR);
			$request->bindParam(':referrer', $records['source']['url'], PDO::PARAM_STR);
			$request->bindParam(':source_type', $records['source']['type'], PDO::PARAM_STR);
			$request->bindParam(':source_search_engine', $records['source']['name'], PDO::PARAM_STR);
			$request->bindParam(':source_keywords', $records['source']['keywords'], PDO::PARAM_STR);

			$request->execute();

			$_SESSION[$settings['session']['name']]['saved'] = true;
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
