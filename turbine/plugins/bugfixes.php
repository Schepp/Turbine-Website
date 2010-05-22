<?php


	/**
	 * A bunch of general browser bugfixes
	 * 
	 * Usage: Nobrainer, just switch it on
	 * Example: -
	 * Status:  Stable
	 * Version: 1.0
	 * 
	 * @param mixed &$parsed
	 * @return void
	 */
	function bugfixes(&$parsed){
		global $cssp, $browser;
		$changed = array();

		// IE 6 global bugfixes
		if($browser->browser == 'ie' && floatval($browser->browser_version) < 7){
			// Image margin bottom bug
			$changed['img']['vertical-align'][] = 'bottom';
			// Background image flickers on hover
			$changed['html']['filter'][] = 'expression(document.execCommand("BackgroundImageCache",false,true))';
		}

		// IE 6 + 7 global bugfixes
		if($browser->browser == 'ie' && floatval($browser->browser_version) < 8){
			// Enable full styleability for IE-buttons, see http://www.sitepoint.com/forums/showthread.php?t=547059
			$changed['button']['overflow'][] = 'visible';
			$changed['button']['width'][] = 'auto';
			$changed['button']['white-space'][] = 'nowrap';
		}

		// Firefox global bugfixes
		if($browser->browser == 'firefox'){
			// Ghost margin around buttons, see http://www.sitepoint.com/forums/showthread.php?t=547059
			$changed['button::-moz-focus-inner']['padding'][] = '0';
			$changed['button::-moz-focus-inner']['border'][] = 'none';
		}

		// Add comments for the global fixes
		foreach($changed as $selector => $styles){
			foreach($styles as $property => $value){
				CSSP::comment($changed[$selector], $property, 'Added by bugfix plugin');
			}
		}

		// Insert the global bugfixes
		$cssp->insert($changed, 'global');

		// Apply per-element-bugfixes
		foreach($cssp->parsed as $block => $css){
			foreach($cssp->parsed[$block] as $selector => $styles){

				// IE 6 per-element-bugfixes
				if($browser->browser == 'ie' && floatval($browser->browser_version) < 7){
					// Float double margin bug, fixed with a behavior as this only affects the floating object and no descendant of it
					if(isset($cssp->parsed[$block][$selector]['float']) && $cssp->get_final_value($cssp->parsed[$block][$selector]['float']) != 'none'){
						$htc_path = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/').'/plugins/bugfixes/doublemargin.htc';
						$cssp->parsed[$block][$selector]['behavior'][] = 'url("'.$htc_path.'")';
						CSSP::comment($cssp->parsed[$block][$selector], 'behavior', 'Added by bugfix plugin');
					}
					// Min-height for IE6
					if(isset($cssp->parsed[$block][$selector]['min-height']) && !isset($cssp->parsed[$block][$selector]['height'])){
						$cssp->parsed[$block][$selector]['height'] = $cssp->parsed[$block][$selector]['min-height'];
						CSSP::comment($cssp->parsed[$block][$selector], 'height', 'Added by bugfix plugin');
					}
				}

				// IE 6 + 7 per-element-bugfixes
				if($browser->browser == 'ie' && floatval($browser->browser_version) < 8){
					// Enable overflow:hidden, if present
					if(isset($cssp->parsed[$block][$selector]['overflow']) && $cssp->get_final_value($cssp->parsed[$block][$selector]['overflow']) == 'hidden' && !isset($cssp->parsed[$block][$selector]['position'])){
						$cssp->parsed[$block][$selector]['position'][] = 'relative';
						CSSP::comment($cssp->parsed[$block][$selector], 'position', 'Added by bugfix plugin');
					}
				}

			}
		}

	}


	/**
	 * Register the plugin
	 */
	$cssp->register_plugin('before_compile', 0, 'bugfixes');


?>
