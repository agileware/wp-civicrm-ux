<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      Civicrm_Ux_Loader $loader Maintains and registers all hooks for the plugin.
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $loader;

	/**
	 * The helper class that provide helper function to all other class
	 *
	 * @var      Civicrm_Ux_Helper $helper The helper class that provide helper function to all other class
	 * @since    1.0.0
	 * @access   public
	 */
	public $helper;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string $civicrm_ux The string used to uniquely identify this plugin.
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $civicrm_ux;

	/**
	 * The current version of the plugin.
	 *
	 * @var      string $version The current version of the plugin.
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'CIVICRM_UXVERSION' ) ) {
			$this->version = CIVICRM_UXVERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->civicrm_ux = 'civicrm-ux';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_shortcodes();
		$this->define_rest();

		$this->loader->add_action( 'init', $this, 'civicrm_init' );
		$this->register_options();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Civicrm_Ux_Loader. Orchestrates the hooks of the plugin.
	 * - Civicrm_Ux_i18n. Defines internationalization functionality.
	 * - Civicrm_Ux_Admin. Defines all hooks for the admin area.
	 * - Civicrm_Ux_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-loader.php';

		/**
		 * The helper class.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-helper.php';


		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-shortcode-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-rest-manager.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/interface/interface-civicrm-ux-shortcode.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/interface/interface-civicrm-ux-rest.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-civicrm-ux-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-civicrm-ux-public.php';

		$this->loader = new Civicrm_Ux_Loader();
		$this->helper = new Civicrm_Ux_Helper( $this );

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Civicrm_Ux_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Civicrm_Ux_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Civicrm_Ux_Admin( $this->get_civicrm_ux(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Civicrm_Ux_Public( $this->get_civicrm_ux(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Register shortcodes.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_shortcodes() {

		$shortcode_manager = new Civicrm_Ux_Shortcode_Manager( $this );

		$this->loader->add_action( 'init', $shortcode_manager, 'register_shortcodes' );
	}

	private function define_rest() {

		$manager = new Civicrm_Ux_REST_Manager( $this );

		$this->loader->add_action( 'rest_api_init', $manager, 'register_rest_routes' );
	}

	private function register_options() {

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_civicrm_ux() {
		return $this->civicrm_ux;
	}

	public function civicrm_init() {
		civicrm_initialize();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Civicrm_Ux_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

}