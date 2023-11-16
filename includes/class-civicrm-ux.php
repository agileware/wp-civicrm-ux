<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both
 * the public-facing side of the site and the admin area.
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

	static private $singleton = NULL;

	static private $directory;

	/**
	 * The loader that's responsible for maintaining and registering all hooks
	 * that power the plugin.
	 *
	 * @var      Civicrm_Ux_Loader $loader Maintains and registers all hooks for
	 *   the plugin.
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $loader;

	/**
	 * The helper class that provide helper function to all other class
	 *
	 * @var      Civicrm_Ux_Helper $helper The helper class that provide helper
	 *   function to all other class
	 * @since    1.0.0
	 * @access   public
	 */
	public $helper;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string $civicrm_ux The string used to uniquely identify this
	 *   plugin.
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

	protected const DEPENDENCIES = [
		'caldera-forms/caldera-core.php' => 'optional',
	];

	/**
	 * @var Civicrm_Ux_Option_Store
	 */
	protected $store;

	static public function getInstance( $plugin_file = '../civicrm-ux.php' ) {
		if ( self::$singleton == NULL ) {
			self::$singleton = new Civicrm_Ux($plugin_file);
		}

		return self::$singleton;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout
	 * the plugin. Load the dependencies, define the locale, and set the hooks
	 * for the admin area and the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	private function __construct( $plugin_file ) {
		$this->version = \get_file_data( realpath( $plugin_file ), [ 'Version' => 'Version' ], 'plugin' )['Version'];
		$this->civicrm_ux = 'civicrm-ux';
		self::$directory = plugin_dir_path( $plugin_file );

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->define_shortcodes();
		$this->define_rest();
		$this->define_magic_tag();

		if( !function_exists( 'urlparam' ) && !empty( self::$directory ) ) {
			include_once( self::$directory . '/packaged/url-params/urlparams.php');
		}

		$this->loader->add_action( 'init', $this, 'civicrm_init' );

		$this->loader->add_action( 'civicrm_config', $this, 'civicrm_config' );

		$this->loader->add_action( 'do_shortcode_tag', $this, 'civicrm_shortcode_filter', 10, 3 );

		$this->loader->add_action( 'civicrm_basepage_parsed', $this, 'civicrm_basepage_actions' );

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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-option-store.php';

		// All module mangers and instances class
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/interface/interface-civicrm-ux-managed-instance.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/abstract-class/class-abstract-civicrm-ux-rest.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/abstract-class/class-abstract-civicrm-ux-shortcode.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/abstract-class/class-abstract-civicrm-ux-cf-magic-tag.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/abstract-class/class-abstract-civicrm-ux-module-manager.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-shortcode-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-rest-manager.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-civicrm-ux-cf-magic-tag-manager.php';

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
		$this->store  = new Civicrm_Ux_Option_Store();

		// Add utility classes
		$this->loader->load( 'includes/utils' );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Civicrm_Ux_i18n class in order to set the domain and to register
	 * the hook with WordPress.
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
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'create_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

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

		// Use the title as set for WordPress in the Avada page titlebar.
		$this->loader->add_filter( 'avada_page_title_bar_contents', $plugin_public, 'avada_page_title_bar_contents' );

		// Use the event time zone from Civi when displaying times in Event Organiser.
		if ( class_exists( 'CiviCRM_WP_Event_Organiser' ) ) {
			$this->loader->add_action( 'eventorganiser_get_the_start', $plugin_public, 'event_organiser_timezone_filter', 10, 5 );
			$this->loader->add_action( 'eventorganiser_get_the_end', $plugin_public, 'event_organiser_timezone_filter', 10, 5 );
		}
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

	private function define_magic_tag() {
		if(!class_exists('Caldera_Forms')) {
			return;
		}

		$manager = new Civicrm_Ux_Cf_Magic_Tag_Manager( $this );

		$this->loader->add_filter( 'caldera_forms_get_magic_tags', $manager, 'register_tags', 10, 2 );
		$this->loader->add_filter( 'caldera_forms_do_magic_tag', $manager, 'dispatch_callback', 10, 2 );
		$this->loader->add_filter( 'caldera_forms_render_start', $manager, 'set_form', 10, 1 );
	}

	private function register_options() {
		// TODO move plugin options here
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		add_action('plugins_loaded', [$this->loader, 'run']);
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
	 * Actions to take during CiviCRM config hook.
	 */
	public function civicrm_config() {
		if ( ( strpos( $_GET['q'] ?? '', 'civicrm/ajax' ) === 0 ) && ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', TRUE );
		}
	}

	public function civicrm_shortcode_filter( $output, $tag, $attr ) {
		global $civicrm_wp_title;
		global $post;

		if ( $tag == 'civicrm' && ( $attr['set_title'] ?? false ) && ! empty( $civicrm_wp_title ) ) {
			$post->post_title = $civicrm_wp_title;

			add_filter( 'single_post_title', [
				civi_wp()->shortcodes,
				'single_page_title',
			], 50, 2 );

			return civi_wp()->shortcodes->get_content( $output );
		}

		return $output;
	}

	/**
	 * Tweak wordpress state on basepage parse.
	 */
	public function civicrm_basepage_actions() {
		global $civicrm_wp_title;
		$title_copy = $civicrm_wp_title;

		$title_func = function ( $title, $id ) use ( $title_copy ) {
			$p = get_post( is_numeric( $id ) ? $id : NULL );

			if ( $p->post_name == CRM_Core_Config::singleton()->wpBasePage ) {
				return $title_copy ?: $title;
			} else {
				return $title;
			}
		};

		// Current post title. CiviCRM should call this one in the first place.
		add_filter( 'the_title', $title_func, 10, 2 );

		// Support "The SEO Framework" title generation.
		add_filter( 'the_seo_framework_title_from_generation', $title_func, 10, 2 );

		// Support Yoast SEO, but only replace the %%title%% string for applicable posts.
		add_filter( 'wpseo_replacements', function ( $replacements, $args ) use ( $title_copy ) {
			if ( isset( $args->post_name ) && ( $args->post_name === CRM_Core_Config::singleton()->wpBasePage ) ) {
				$replacements['%%title%%'] = $title_copy;
			}

			return $replacements;
		}, 10, 2 );
	}

	/**
	 * Fake the current post being the CiviCRM basepage for one callback.
	 *
	 * @param Closure $callback
	 *
	 * @return any
	 */
	public static function in_basepage( Closure $callback ) {
		global $post;
		$actual_post = $post;

		$post = get_page_by_path( strtolower( CRM_Core_Config::singleton()->wpBasePage ?? 'civicrm' ) ) ?? $post;

		$return = $callback();

		$post = $actual_post;

		return $return;
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

	public function get_store() {
		return $this->store;
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
