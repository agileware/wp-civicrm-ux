<?php

/**
 * Interface iCivicrm_Ux_Shortcode
 */
abstract class Abstract_Civicrm_Ux_Shortcode implements iCivicrm_Ux_Managed_Instance {
	protected $manager;

	/**
	 * @param \Civicrm_Ux_Shortcode_Manager $manager
	 */
	public function __construct( $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @return string The name of shortcode
	 */
	abstract public function get_shortcode_name();

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	abstract public function shortcode_callback( $atts = [], $content = null, $tag = '' );
}