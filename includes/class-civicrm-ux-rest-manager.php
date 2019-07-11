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
class Civicrm_Ux_REST_Manager extends Abstract_Civicrm_Ux_Module_Manager {

	public function __construct( $plugin ) {
		parent::__construct( $plugin );
	}

	public function register_rest_routes() {
		if ( empty( $this->instances ) ) {
			return;
		}

		foreach ( $this->instances as $instance ) {
			register_rest_route( $instance->get_route(), $instance->get_endpoint(), [
				'methods'  => $instance->get_method(),
				'callback' => [
					$instance,
					'rest_api_callback'
				]
			] );
		}
	}

	/**
	 * @return string
	 */
	public function get_directory() {
		return 'rest';
	}

	/**
	 * @return string
	 */
	public function get_managed_interface() {
		return 'Abstract_Civicrm_Ux_REST';
	}
}
