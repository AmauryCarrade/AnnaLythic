(function() {
	if(navigator.doNotTrack) return;

	/*************************************************
	 * AnnaLythics -- TRACKER XHR SETTINGS
	 */

	// Path to AnnaLithic/al.php (absolute [recommended] or relative to the webpage, NOT  to al.js).
	var pathToAlPHP = 'AnnaLythic/al.php';

	/*************************************************
	 * Configuration done; close this file, now ;) .
	 */

	navigator.plugins.refresh();

	var JSONPlugins = '{';
	if(navigator.plugins.length != 0) {
		for(id in navigator.plugins) {
			JSONPlugins += '"' + id + '":"' + navigator.plugins[id].name + '"';
			if(parseFloat(id)+1 != navigator.plugins.length) JSONPlugins += ',';
			else break;
		}
	}
	JSONPlugins += '}';

	var JSON = '{';
	JSON += '"url":"' + document.URL + '",';
	JSON += '"referrer":"' + document.referrer + '",';
	JSON += '"screen":{';
		JSON += '"width":"' + screen.width + '",';
		JSON += '"height":"' + screen.height + '",';
		JSON += '"colorDepth":"' + window.screen.colorDepth + '",';
		JSON += '"fontSmoothing":"' + window.screen.fontSmoothingEnabled + '"';
	JSON += '},';
	JSON += '"browser":{';
		JSON += '"language":"' + navigator.language + '",';
		JSON += '"cookie":"';
		JSON += navigator.cookieEnabled ? 'true' : 'false';
		JSON += '",';
		JSON += '"plugins":' + JSONPlugins + '}';
	JSON += '}';

	// Sending request
	var xhr = null;
	
	if (window.XMLHttpRequest || window.ActiveXObject) {
		if (window.ActiveXObject) {
			try {
				xhr = new ActiveXObject("Msxml2.XMLHTTP");
			} catch(e) {
				xhr = new ActiveXObject("Microsoft.XMLHTTP");
			}
		} else {
			xhr = new XMLHttpRequest(); 
		}
	} else {
		// Sorry, no analytics for this user. Unable to save them.
		return;
	}

	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
			if(xhr.responseText != '') {
				if(xhr.responseText.indexOf('|') !== false) {
					if(xhr.responseText.split('|')[0] == 'alert') {
						alert(xhr.responseText.split('|')[1]);
					}
					else {
						console.log(xhr.responseText);
					}
				}
				else {
					console.log(xhr.responseText);
				}
			}
		}
	};
	xhr.open("POST", pathToAlPHP, true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send('dump=' + encodeURIComponent(JSON));
})();
