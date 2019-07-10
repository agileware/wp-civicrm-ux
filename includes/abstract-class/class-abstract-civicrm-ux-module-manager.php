<?php

abstract class Abstract_Civicrm_Ux_Module_manager {
	/**
	 * @var \Civicrm_Ux $plugin the plugin instance
	 */
	protected $plugin;

	/**
	 * @var iCivicrm_Ux_Managed_Instance[] array
	 */
	protected $instances;

	public function __construct( $plugin ) {
		$this->plugin            = $plugin;
		$this->instances         = [];

		$this->plugin->get_loader()->load( $this->get_directory(), $this, $this->get_managed_interface() );
	}

	/**
	 * Add instances
	 *
	 * @param iCivicrm_Ux_Managed_Instance $instance
	 */
	public function add_instance( $instance ) {
		if ( ! in_array( $instance, $this->instances ) ) {
			$this->instances[] = $instance;
		}
	}

	public function get_plugin() {
		return $this->plugin;
	}

	/**
	 * The root directory of the manager instance php file.
	 * Should start from the root of plugin
	 *
	 * @return string
	 */
	abstract public function get_directory();

	/**
	 * The interface or class name to identify the instance
	 *
	 * @return string
	 */
	abstract public function get_managed_interface();

	/**
	 * This is the callback function after loader finished loading php files and
	 * generated all managed instances.
	 */
	public function after_instance_load() {

	}
}