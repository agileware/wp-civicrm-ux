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

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/civicrm-ux-admin-display.php';
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

	public function create_menu() {
		add_options_page( 'CiviCRM UX Setting', 'CiviCRM UX', 'administrator', 'civicrm-ux-setting.php', 'civicrm_ux_settings_page' );
	}

	public function register_settings() {
		//register our settings
		register_setting( 'civicrm-ux-settings-group', Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION );
		register_setting( 'civicrm-ux-settings-group', 'civicrm_summary_options' );
	}
}
