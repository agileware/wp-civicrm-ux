<?php

/**
 * Register all shortcodes
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 */

/**
 * Register all shortcodes
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux_Shortcode_Manager extends Abstract_Civicrm_Ux_Module_manager {

	/**
	 *
	 * @param \Civicrm_Ux $plugin
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin ) {
		parent::__construct( $plugin );
	}

	/**
	 * Hooked action init
	 */
	public function register_shortcodes() {
		if ( empty( $this->instances ) ) {
			return;
		}

		foreach ( $this->instances as $instance ) {
			add_shortcode( $instance->get_shortcode_name(), [ $instance, 'shortcode_callback' ] );
		}
	}

	/**
	 * The root directory of the manager instance php file.
	 * Should start from the root of plugin
	 *
	 * @return string
	 */
	public function get_directory() {
		return 'shortcodes';
	}

	/**
	 * The interface or class name to identify the instance
	 *
	 * @return string
	 */
	public function get_managed_interface() {
		return 'Abstract_Civicrm_Ux_Shortcode';
	}
}
