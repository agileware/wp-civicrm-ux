<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/admin
 * @author     Agileware <support@agileware.com.au>
 */
class Agileware_Civicrm_Utilities_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var      string $agileware_civicrm_utilities The ID of this plugin.
	 * @since    1.0.0
	 * @access   private
	 */
	private $agileware_civicrm_utilities;

	/**
	 * The version of this plugin.
	 *
	 * @var      string $version The current version of this plugin.
	 * @since    1.0.0
	 * @access   private
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $agileware_civicrm_utilities The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $agileware_civicrm_utilities, $version ) {

		$this->agileware_civicrm_utilities = $agileware_civicrm_utilities;
		$this->version                     = $version;

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/agileware-civicrm-utilities-admin-display.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Agileware_Civicrm_Utilities_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Agileware_Civicrm_Utilities_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->agileware_civicrm_utilities, plugin_dir_url( __FILE__ ) . 'css/agileware-civicrm-utilities-admin.css', [], $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Agileware_Civicrm_Utilities_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Agileware_Civicrm_Utilities_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->agileware_civicrm_utilities, plugin_dir_url( __FILE__ ) . 'js/agileware-civicrm-utilities-admin.js', [ 'jquery' ], $this->version, FALSE );

	}

}
