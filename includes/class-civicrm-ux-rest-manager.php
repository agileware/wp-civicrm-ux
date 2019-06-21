<?php

/**
 * Register all REST APIs
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 */

/**
 * Register all REST APIs
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux_REST_Manager {

	/**
	 * @var \Civicrm_Ux $plugin the plugin instance
	 */
	protected $plugin;

	/**
	 * @var \iCivicrm_Ux_REST[] A list of registered REST APIs.
	 */
	protected $registered_instances;

	/**
	 * The directory
	 */
	protected const DIRECTORY = 'rest';

	/**
	 * Initialize
	 *
	 * @param \Civicrm_Ux $plugin
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
			if ( in_array( 'iCivicrm_Ux_REST', class_implements( $className ) ) ) {
				/** @var \iCivicrm_Ux_REST $instance */
				$instance = new $className();
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
			register_rest_route( $instance->get_route(), $instance->get_endpoint(), [
				'methods'  => $instance->get_method(),
				'callback' => [
					$instance,
					'rest_api_callback'
				]
			] );
		}
	}

	public function get_plugin() {
		return $this->plugin;
	}
}
