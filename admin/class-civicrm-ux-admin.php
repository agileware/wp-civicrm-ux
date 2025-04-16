<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/admin
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @var      string $civicrm_ux The ID of this plugin.
	 * @since    1.0.0
	 * @access   private
	 */
	private $civicrm_ux;

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
	 * @param string $civicrm_ux The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $civicrm_ux, $version ) {

		$this->civicrm_ux = $civicrm_ux;
		$this->version    = $version;
		
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
		 * defined in Civicrm_Ux_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Civicrm_Ux_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->civicrm_ux, plugin_dir_url( __FILE__ ) . 'css/civicrm-ux-admin.css', [], $this->version, 'all' );

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
		 * defined in Civicrm_Ux_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Civicrm_Ux_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->civicrm_ux, plugin_dir_url( __FILE__ ) . 'js/civicrm-ux-admin.js', [ 'jquery' ], $this->version, false );

	}

	/**
	 * Dequeue specific stylesheets for the admin area, usually because they conflict with CiviCRM
	 *
	 * @since    1.31.2
	 */
	public function dequeue_styles()
	{

		if (isset($_GET['page'])) {
			$page = $_GET['page'];
			// Video User Manuals plugin, should only load on their specific pages
			if (strpos($page, 'video-user-manuals') === false && strpos($page, 'vum-options') === false) {
				wp_dequeue_style('vumcss');
			}
		}
	}

	/**
	 * Dequeue specific scripts for the admin area, usually because they conflict with CiviCRM
	 *
	 * @since    1.31.2
	 */
	public function dequeue_scripts() {
		// Define any scripts to dequeue here
	}

	public function create_menu() {
		add_filter('admin_footer_text', '__return_empty_string');
	}
}
