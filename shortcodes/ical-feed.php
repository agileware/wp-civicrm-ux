<?php

class Agileware_Civicrm_Utilities_Shortcode_ICal_Feed implements iAgileware_Civicrm_Utilities_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ical-feed';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = NULL, $tag = '' ) {
		return 'set up successfully.';
	}
}