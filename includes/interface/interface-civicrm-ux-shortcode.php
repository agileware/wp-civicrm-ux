<?php

/**
 * Interface iCivicrm_Ux_Shortcode
 */
interface iCivicrm_Ux_Shortcode {

	/**
	 * @param \Civicrm_Ux_Shortcode_Manager $manager
	 *
	 * @return mixed
	 */
	public function init_setup( Civicrm_Ux_Shortcode_Manager $manager );

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
	public function shortcode_callback( $atts = [], $content = null, $tag = '' );
}