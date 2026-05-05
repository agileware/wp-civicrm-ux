<?php

abstract class Abstract_Civicrm_Ux_REST implements iCivicrm_Ux_Managed_Instance {
	protected $manager;

	/**
	 * @param \Civicrm_Ux_REST_Manager $manager
	 */
	public function __construct( $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @return string
	 */
	abstract public function get_route();

	/**
	 * @return string
	 */
	abstract public function get_endpoint();

	/**
	 * @return string
	 */
	abstract public function get_method();

	/**
	 * @param \WP_REST_Request $data
	 *
	 * @return mixed
	 */
	abstract public function rest_api_callback( $data );

	/**
	 * Check permissions for REST endpoint
	 *
	 * @return bool
	 */
	public function check_permissions() {
		// By default, allow access if user is logged in
		// Child classes can override for more specific permission checks
		return is_user_logged_in();
	}
}