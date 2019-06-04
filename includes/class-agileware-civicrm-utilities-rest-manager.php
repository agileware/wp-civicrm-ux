<?php

/**
 * Register all REST APIs
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/includes
 */

/**
 * Register all REST APIs
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Agileware_Civicrm_Utilities_REST_Manager {

	/**
	 * @var \Agileware_Civicrm_Utilities $plugin the plugin instance
	 */
	protected $plugin;

	/**
	 * @var \iAgileware_Civicrm_Utilities_REST[] A list of registered REST APIs.
	 */
	protected $registered_instances;

	/**
	 * The directory
	 */
	protected const DIRECTORY = 'rest';

	/**
	 * Initialize
	 *
	 * @param \Agileware_Civicrm_Utilities $plugin
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin ) {
		$this->plugin               = $plugin;
		$this->registered_instances = [];

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
			if ( in_array( 'iAgileware_Civicrm_Utilities_REST', class_implements( $className ) ) ) {
				/** @var \iAgileware_Civicrm_Utilities_REST $instance */
				$instance                     = new $className();
				$instance->init_setup( $this );
				$this->registered_instances[] = $instance;
			}
		}
	}

	/**
	 * Hook to action rest_api_init
	 */
	public function register_rest_routes() {
		if ( empty( $this->registered_instances ) ) {
			return;
		}

		foreach ( $this->registered_instances as $instance ) {
			register_rest_route( $instance->get_route(), $instance->get_endpoint(), [ 'methods' => $instance->get_method(), 'callback' => [ $instance, 'rest_api_callback' ] ] );
		}
	}

	public function get_plugin() {
		return $this->plugin;
	}
}
