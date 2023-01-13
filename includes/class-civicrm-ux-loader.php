<?php

/**
 * Register all actions and filters for the plugin
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux_Loader {

	/**
	 * The array of actions registered with WordPress.
	 *
	 * @var      array $actions The actions registered with WordPress to fire when the plugin loads.
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $actions;

	/**
	 * The array of filters registered with WordPress.
	 *
	 * @var      array $filters The filters registered with WordPress to fire when the plugin loads.
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $filters;

	protected $files;

	protected $interfaces;

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->actions    = [];
		$this->filters    = [];
		$this->files      = [];
		$this->interfaces = [];

	}

	/**
	 * Add a new action to the collection to be registered with WordPress.
	 *
	 * @param string $hook The name of the WordPress action that is being registered.
	 * @param object $component A reference to the instance of the object on which the action is defined.
	 * @param string $callback The name of the function definition on the $component.
	 * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
	 * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
	 *
	 * @since    1.0.0
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection to be registered with WordPress.
	 *
	 * @param string $hook The name of the WordPress filter that is being registered.
	 * @param object $component A reference to the instance of the object on which the filter is defined.
	 * @param string $callback The name of the function definition on the $component.
	 * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
	 * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1
	 *
	 * @since    1.0.0
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * A utility function that is used to register the actions and hooks into a single
	 * collection.
	 *
	 * @param array $hooks The collection of hooks that is being registered (that is, actions or filters).
	 * @param string $hook The name of the WordPress filter that is being registered.
	 * @param object $component A reference to the instance of the object on which the filter is defined.
	 * @param string $callback The name of the function definition on the $component.
	 * @param int $priority The priority at which the function should be fired.
	 * @param int $accepted_args The number of arguments that should be passed to the $callback.
	 *
	 * @return   array                                  The collection of actions and filters registered with WordPress.
	 * @since    1.0.0
	 * @access   private
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {

		$hooks[] = [
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];

		return $hooks;

	}

	/**
	 * @param string $relative_path the relative path. Plugin directory will be added inside this function
	 * @param object $manager the manager instance
	 * @param string $interface the interface or class for managed instance
	 * @param bool $recursive
	 *
	 * @since    1.0.0
	 */
	public function load( $relative_path, $manager = null, $interface = null, $recursive = true ) {
		$path = plugin_dir_path( dirname( __FILE__ ) ) . "/$relative_path";
		if ( ! empty( $manager ) && ! empty( $interface ) ) {
			$this->add_interface( $manager, $interface );
		}

		// If is a single file, add it
		if ( ! is_dir( $path ) || $this->is_php_file( $path ) ) {
			$this->add_file( $path );
		} else if ( is_dir( $path ) ) {
			foreach ( scandir( $path ) as $filename ) {
				if ( in_array( $filename, [ '.', '..' ] ) ) {
					continue;
				}
				$path = plugin_dir_path( dirname( __FILE__ ) ) . '/' . $relative_path . '/' . $filename;
				// Recursive
				if ( is_dir( $path ) && $recursive ) {
					$this->load( $relative_path . '/' . $filename, $manager, $interface );
				} else if ( $this->is_php_file( $path ) ) {
					$this->add_file( $path );
				}
			}
		}
	}

	public function add_interface( $manager, $interface ) {
		if ( ! in_array( $interface, array_keys( $this->interfaces ) ) ) {
			$this->interfaces[ $interface ] = $manager;
		}
	}

	/**
	 * Add the php path for loader to load
	 *
	 * @param string $path the absolute path
	 */
	private function add_file( $path ) {
		if ( ! in_array( $path, $this->files ) ) {
			$this->files[] = $path;
		}
	}

	/**
	 * @param string $path absolute path
	 *
	 * @return bool false if the path is not a php file
	 */
	private function is_php_file( $path ) {
		$file_info = pathinfo( $path );

		return is_file( $path ) && $file_info['extension'] == 'php';
	}

	/**
	 * @param string $path absolute path
	 *
	 * @return bool false if failed
	 */
	private function require_php_file( $path ) {
		if ( $this->is_php_file( $path ) ) {
			require_once $path;

			return true;
		}

		return false;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], [
				$hook['component'],
				$hook['callback']
			], $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], [
				$hook['component'],
				$hook['callback']
			], $hook['priority'], $hook['accepted_args'] );
		}

		// PHP files
		foreach ( $this->files as $path ) {
			$this->require_php_file( $path );
		}

		foreach ( $this->interfaces as $interface => $manager ) {
			/** @var Abstract_Civicrm_Ux_Module_Manager $manager */
			foreach ( get_declared_classes() as $className ) {
				if ( in_array( $interface, class_implements( $className ) )
				     || is_subclass_of( $className, $interface ) ) {
					/** @var iCivicrm_Ux_Managed_Instance $instance */
					if ( method_exists( $className, 'getInstances' ) ) {
						foreach($className::getInstances( $manager ) as $instance) {
							$manager->add_instance($instance);
						};
					} else {
						$instance = new $className( $manager );
						$manager->add_instance( $instance );
					}
				}
			}

			$manager->after_instance_load();
		}
	}
}
