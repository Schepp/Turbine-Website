<?php 

	/**
	 * Legacy
	 * Meta plugin, activates all other plugins that help you to support older browsers
	 * 
	 * Usage: See the individual plugins' descriptions
	 * Example: -
	 * Status: Stable
	 * Version: 1.0
	 * 
	 * @param array &$css The style lines (unused)
	 * @return void
	 */
	function legacy(&$css){
		global $plugin_list;
		$legacyplugins = array(
			'ie6enhancements',
			'bugfixes',
			'inlineblock'
		);
		$plugin_list = array_merge($plugin_list, $legacyplugins);
	}


	/**
	 * Register the plugin
	 */
	$cssp->register_plugin('before_parse', 1000, 'legacy');


?>