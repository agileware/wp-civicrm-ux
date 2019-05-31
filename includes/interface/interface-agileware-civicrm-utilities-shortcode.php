<?php

/**
 * Interface iAgileware_Civicrm_Utilities_Shortcode
 */
interface iAgileware_Civicrm_Utilities_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name();

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback($atts = [], $content = null, $tag = '');
}