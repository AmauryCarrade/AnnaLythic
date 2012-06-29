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



	$userAgent = $dump->browser->userAgent;

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
	
	testOS('iPad', NULL, '#iPad; U; CPU OS ([0-9_]{3-5})#', '_'); // Check this
	testOS('iPhone', NULL, '#iPhone OS ([0-9_]{3-5})#', '_');
	testOS('iPod Touch', '#iPod#', '#iPhone OS ([0-9_]{3-5})#', '_');
	testOS('Android', NULL, '#Android ([0-9.]{3-})#');
	testOS('BlackBerry', NULL, '#Version/([0-9.]{5})#');
	
	testOS('Windows 8', '#Windows NT 6.2#', '8');
	testOS('Windows 7', '#Windows NT 6.1#', '7');
	testOS('Windows Vista', '#Windows NT 6.0#', 'Vista');
	testOS('Windows XP', '#Windows NT 5.1#', 'XP');
	testOS('Windows Server 2003', '#Windows NT 5.2#', '2003');
	testOS('Windows 2000', '#Windows NT 5.0#', '2000');
	testOS('Windows NT', '#Windows NT#', 'NT');
	testOS('Windows 98', '#Windows 98#', '98');
	testOS('Windows 95', '#Windows 95#', '95');
	testOS('Windows 3.1', '#Windows 3.1#', '3.1');
	testOS('Unknow Windows', '#Windows#');

	testOS('FreeBSD');
	testOS('Mac OS X');
	testOS('Ubuntu Linux', '#Ubuntu#i', '#Ubuntu/([0-9.]{4-5})#');
	testOS('Fedora');
	testOS('Gentoo', '#gentoo#i');
	testOS('Kanotix', '#kanotix#i');
	testOS('Open Solaris', '#SunOS#');
	testOS('Unknow Linux', '#Linux#');

	testOS('Irix', '#IRIX#');
	testOS('BeOS');
	testOS('SymbianOS', NULL, '#SymbianOS/([0-9.]{3})#');
