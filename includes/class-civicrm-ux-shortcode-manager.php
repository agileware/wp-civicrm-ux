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
class Civicrm_Ux_Shortcode_Manager {

	/**
	 * @var \Civicrm_Ux $plugin the plugin instance
	 */
	protected $plugin;

	/**
	 * @var \iCivicrm_Ux_Shortcode[] A list of registered shortcodes.
	 */
	protected $registered_shortcodes;

	/**
	 * The directory
	 */
	protected const DIRECTORY = 'shortcodes';

	/**
	 * Initialize
	 *
	 * @param \Civicrm_Ux $plugin
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin ) {
		$this->plugin                = $plugin;
		$this->registered_shortcodes = [];

		$this->run();
	}

	/**
	 *
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$paths = $this->plugin->helper->get_php_file_paths( self::DIRECTORY );
		foreach ( $paths as $path ) {
			if ( $this->plugin->helper->require_php_file( $path ) ) {
				// Do something...
			}
		}

		foreach ( get_declared_classes() as $className ) {
			if ( in_array( 'iCivicrm_Ux_Shortcode', class_implements( $className ) ) ) {
				/** @var \iCivicrm_Ux_Shortcode $instance */
				$instance = new $className();
				$instance->init_setup( $this );
				$this->registered_shortcodes[] = $instance;
			}
		}
	}

	/**
	 * Hooked action init
	 */
	public function register_shortcodes() {
		if ( empty( $this->registered_shortcodes ) ) {
			return;
		}

		foreach ( $this->registered_shortcodes as $instance ) {
			add_shortcode( $instance->get_shortcode_name(), [ $instance, 'shortcode_callback' ] );
		}
	}

	public function get_plugin() {
		return $this->plugin;
	}
}
