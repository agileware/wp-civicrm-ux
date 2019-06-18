<?php

/**
 * Register all shortcodes
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/includes
 */

/**
 * Register all shortcodes
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Agileware_Civicrm_Utilities_Shortcode_Manager {

	/**
	 * @var \Agileware_Civicrm_Utilities $plugin the plugin instance
	 */
	protected $plugin;

	/**
	 * @var \iAgileware_Civicrm_Utilities_Shortcode[] A list of registered shortcodes.
	 */
	protected $registered_shortcodes;

	/**
	 * The directory
	 */
	protected const DIRECTORY = 'shortcodes';

	/**
	 * Initialize
	 *
	 * @param \Agileware_Civicrm_Utilities $plugin
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
			if ( in_array( 'iAgileware_Civicrm_Utilities_Shortcode', class_implements( $className ) ) ) {
				/** @var \iAgileware_Civicrm_Utilities_Shortcode $instance */
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
