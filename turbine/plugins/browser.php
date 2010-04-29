<?php

	/**
	 * Browser
	 * Implements the "browser", "engine" and "device" properties for browser detection
	 * 
	 * Usage: browser:mybrowser myotherbrowser;
	 * Usage: engine:myengine myotherengine;
	 * Usage: device:mydevice myotherdevice;
	 * If there are multiple arguments defined: Any positive submatch with the rule makes a positive match out of the whole rule
	 * 
	 * 
	 * 
	 * browser matches the browser
	 * possible browsers-names are:
	 * opera_mini
	 * opera
	 * netscape
	 * flock
	 * mozilla
	 * minimo
	 * fennec
	 * minefield
	 * firebird
	 * k-meleon
	 * seamonkey
	 * orca
	 * firefox
	 * gecko
	 * safari
	 * chromeframe
	 * chrome
	 * omniweb
	 * shiira
	 * arora
	 * midori
	 * icab
	 * webkit
	 * konqueror
	 * aol
	 * avant_browser
	 * maxthon
	 * msie (which is the internet explorer)
	 * 
	 * Example 1: browser:firefox; - CSS rules only apply on firefox (Simple detection)
	 * Example 2: browser:^firefox; - CSS rules apply everywhere except firefox (Simple exclusion)
	 * Example 3: browser:firefox<3.5; - CSS rules only apply on firefox versions older than 3.5 (detection by version number)
	 * Example 3: browser:firefox<=3.5; - CSS rules only apply on firefox versions older than or equal to 3.5 (detection by version number)
	 * Example 4: browser:firefox opera; - CSS rules only apply on firefox OR opera (Multi-Detection)
	 * 
	 * 
	 * 
	 * engine matches the browser's underlying engine
	 * possible engine-names are (versioning-syntax is different on each):
	 * 
	 * opera
	 * gecko
	 * webkit
	 * icab
	 * khtml
	 * msie
	 * 
	 * Example 1: engine:gecko; - CSS rules only apply on browsers with gecko-engine (Simple detection)
	 * Example 2: engine:^gecko; - CSS rules apply everywhere except on browsers with gecko-engine (Simple exclusion)
	 * Example 3: engine:gecko<1.92; - CSS rules only apply on browsers with gecko-engine older than 1.92 (detection by version number)
	 * Example 3: engine:gecko<=1.92; - CSS rules only apply on browsers with gecko-engine older than or equal to 1.92 (detection by version number)
	 * Example 4: engine:gecko webkit; - CSS rules only apply on on browsers with gecko- OR webkit-engine (Multi-Detection)
	 * 
	 * 
	 * 
	 * device matches the user's device
	 * possible device-names are:
	 * 
	 * desktop
	 * mobile
	 * 
	 * Example 1: device:desktop; - CSS rules only apply on desktop-browsers (Simple detection)
	 * Example 2: device:mobile; - CSS rules only apply on mobile-browsers (Simple detection)
	 * 
	 * "mobile" not only matches portable devices but also game-consoles
	 */


	/**
	 * browser
	 * Main plugin function
	 * @param mixed &$parsed
	 * @return void
	 */
	function browser(&$parsed){
		// Look for a browser rule in @cssp, empty $parsed on mismatch
		if(isset($parsed['global']['@cssp']['browser']) ||
			isset($parsed['global']['@cssp']['engine']) ||
			isset($parsed['global']['@cssp']['device'])
		){
			$browserparsed = browser_parse_browser($parsed['global']['@cssp']);
			$browserparsed = browser_parse_engine($browserparsed);
			$browserparsed = browser_parse_device($browserparsed);
			if($browserparsed === false){
				$parsed = array();
			}
		}
		foreach($parsed as $block => $css){
			foreach($parsed[$block] as $selector => $styles){
				// Loop through @font-face
				if($selector == '@font-face'){
					foreach($styles as $index => $style){
						$browserparsed = browser_parse_browser($style);
						$browserparsed = browser_parse_engine($browserparsed);
						$browserparsed = browser_parse_device($browserparsed);
						if($browserparsed){
							$parsed[$block][$selector][$index] = $browserparsed;
						}
						else {
							unset($parsed[$block][$selector][$index]);
						}
					}
				}
				// Parse the rest
				else{
					$browserparsed = browser_parse_browser($styles);
					$browserparsed = browser_parse_engine($browserparsed);
					$browserparsed = browser_parse_device($browserparsed);
					if($browserparsed){
						$parsed[$block][$selector] = $browserparsed;
					}
					else{
						unset($parsed[$block][$selector]);
					}
				}
			}
		}
	}


	/**
	 * browser_parse_browser
	 * Looks for the "browser" property in an element and parses it
	 * @param object $styles
	 * @return 
	 */
	function browser_parse_browser($styles){
		global $browser;
		$match = true;
		// Find browser property
		if(isset($styles['browser'])){
			$match = false;
			// Split up any multiple browser rules in order to check them one by one
			$browserrules = preg_split('/\s+/', $styles['browser']);
			// Check each browser rule
			foreach($browserrules as $browserrule){
				preg_match('/([\^]?)([a-z\-_0-9]+)([!=><]{0,2})([0-9]*\.?[0-9]*]*)/i', $browserrule, $matches);
				// If the useragent's detected browser is found in the current rule
				if(strstr(strtolower($matches[2]),strtolower(str_replace(' ','_',$browser->name)))){
					// For the time being set $submatch to true
					$submatch = true;
					// If we found a logical operator and a version number
					if($matches[3] != '' && $matches[4] == floatval($matches[4])){
						// Turn a single =-operator into a PHP-interpretable ==-operator
						if($matches[3] == '=') $matches[3] = '==';
						// Filter and run the detected rule through the PHP interpreter
						eval('if('.floatval($browser->version).$matches[3].floatval($matches[4]).') $submatch = true; else $submatch = false;');
					}
				}
				else{
					// Set $submatch to false
					$submatch = false;
				}
				// Check if we had a negating operator at the beginning and in case flip result
				if($matches[1] == '^') $submatch = ($submatch == true) ? false : true;
				// Check the final state of $submatch and set $match only to true if $submatch is true
				if($submatch) $match = true;
			}
		}
		// Keep the styles, unset browser property
		if($match){
			unset($styles['browser']);
			return $styles;
		}
		// Remove the element
		else {
			return false;
		}
	}


	/**
	 * browser_parse_engine
	 * Looks for the "engine" property in an element and parses it
	 * 
	 * @param object $styles
	 * @return 
	 */
	function browser_parse_engine($styles){
		global $browser;
		$match = true;
		// Find engine property
		if(isset($styles['engine'])){
			$match = false;
			// Split up any multiple engine rules in order to check them one by one
			$browserrules = preg_split('/\s+/', $styles['engine']);
			// Check each engine rule
			foreach($browserrules as $browserrule){
				preg_match('/([\^]?)([a-z\-_0-9]+)([!=><]{0,2})([0-9]*\.?[0-9]*]*)/i', $browserrule, $matches);
				// If the useragent's detected engine is found in the current rule
				if(strstr(strtolower($matches[2]),strtolower(str_replace(' ','_',$browser->engine))))
				{
					// For the time being set $submatch to true
					$submatch = true;
					// If we found a logical operator and a version number
					if($matches[3] != '' && $matches[4] == floatval($matches[4]))
					{
						// Turn a single =-operator into a PHP-interpretable ==-operator
						if($matches[3] == '=') $matches[3] = '==';
						// Filter and run the detected rule through the PHP interpreter
						eval('if('.floatval($browser->engineversion).$matches[3].floatval($matches[4]).') $submatch = true; else $submatch = false;');
					}
				}
				else
				{
					// Set $submatch to false
					$submatch = false;
				}
				// Check if we had a negating operator at the beginning and in case flip result
				if($matches[1] == '^') $submatch = ($submatch == true) ? false : true;
				// Check the final state of $submatch and set $match only to true if $submatch is true
				if($submatch) $match = true;
			}
		}
		// Keep the styles, unset engine property
		if($match){
			unset($styles['engine']);
			return $styles;
		}
		// Remove the element
		else {
			return false;
		}
	}


	/**
	 * browser_parse_device
	 * Looks for the "device" property in an element and parses it
	 * 
	 * @param object $styles
	 * @return 
	 */
	function browser_parse_device($styles){
		global $browser;
		$match = true;
		// Find device property
		if(isset($styles['device'])){
			$match = false;
			// Split up any multiple device rules in order to check them one by one
			$browserrules = preg_split('/\s+/', $styles['device']);
			// Check each device rule
			foreach($browserrules as $browserrule){
				if(strtolower($browserrule) == strtolower(str_replace(' ','_',$browser->platformtype))) $submatch = true;
				else $submatch = false;
				// Check if we had a negating operator at the beginning and in case flip result
				if($matches[1] == '^') $submatch = ($submatch == true) ? false : true;
				// Check the final state of $submatch and set $match only to true if $submatch is true
				if($submatch) $match = true;
			}
		}
		// Keep the styles, unset device property
		if($match){
			unset($styles['device']);
			return $styles;
		}
		// Remove the element
		else {
			return false;
		}
	}


	/**
	 * Register the plugin
	 */
	$cssp->register_plugin('before_compile', 1000, 'browser');


?>