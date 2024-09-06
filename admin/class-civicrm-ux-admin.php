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
	 * A list of plugins blacklisted from activation.
	 * 
	 * Define plugin paths here.
	 * Define the corresponding defaults for civicrm_plugin_activation_blocks setting in Civicrm_Ux_Option_Store.
	 */
	private $blocked_plugins = array(
		'event_tickets' => 'event-tickets/event-tickets.php',
	);

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
		add_filter('admin_footer_text', '__return_empty_string');
	}

	public function register_settings() {
		//register our settings
		register_setting( 'civicrm-ux-settings-group', Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION );
		register_setting( 'civicrm-ux-settings-group', 'civicrm_summary_options' );
		register_setting( 'civicrm-ux-settings-group', 'civicrm_contribution_ux' );

		// WPCIVIUX-148 settings
		register_setting( 'civicrm-ux-settings-group', 'civicrm_plugin_activation_blocks' );

		// GFCV-107 settings
		register_setting( 'civicrm-ux-settings-group', 'self_serve_checksum', 
			array(
				'type' => 'array',
				'sanitize_callback' => function($input) {
					// Custom sanitize callback to handle HTML content
					$sanitized = [];
					foreach ($input as $field => $value) {
						switch ($field) {
							case 'form_text':
								// Use wp_kses_post to allow only safe HTML for WYSIWYG content
								$sanitized[$field] = wp_kses_post($value);
								break;
							case 'email_message':
								// Validate that the {checksum_url} custom token is present in the content
								if (strpos($value, '{checksum_url}') === false) {
									// Add a settings error if the token is missing
									add_settings_error(
										'self_serve_checksum', // Slug title of the setting
										'missing_checksum_token', // Error code
										'The Self Serve Checksum email message must include the {checksum_url} to be valid.', // Error message
										'error' // Type of the message
									);
	
									// Prevent saving by returning false
									return false;
								}
								// Use wp_kses_post to allow only safe HTML for WYSIWYG content
								$sanitized[$field] = wp_kses_post($value);
								break;
							default:
								$sanitized[$field] = sanitize_text_field($value);
								break;
						}
					}

					return $sanitized;
				},
			)
		);
	}

	/**
	 * Blacklist plugins from activating
	 * 
	 * WPCIVIUX-148 : event-tickets
	 */
	public function prevent_blacklisted_plugin_activation($plugin, $network_wide) {
		// Get the list of plugins to block
		$plugin_activation_blocks = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option('civicrm_plugin_activation_blocks');

		$plugin_activation_blocks = is_array($plugin_activation_blocks) ? $plugin_activation_blocks : [];

		// Get the plugin slug from the plugin path
		$plugin_slug = str_replace('-', '_', dirname($plugin));

		// Check if the plugin is in the list of blocked plugins and if it should be blocked
		if (isset($this->blocked_plugins[$plugin_slug]) && isset($plugin_activation_blocks[$plugin_slug]) && $plugin_activation_blocks[$plugin_slug]) {
			// Abort the activation process and display an error message
			wp_die(__('This plugin is blocked and cannot be activated.', 'textdomain'), __('Plugin Activation Error', 'textdomain'), array('back_link' => true));
		}
	}

	/**
	 * Deactivate currently active blacklisted plugins.
	 * 
	 * This is in case plugins have been blacklisted after they were already activated.
	 * 
	 * WPCIVIUX-148 : event-tickets
	 */
	public function deactivate_blacklisted_plugins() {
		// Get the list of plugins to block
		$plugin_activation_blocks = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option('civicrm_plugin_activation_blocks');

		$plugin_activation_blocks = is_array($plugin_activation_blocks) ? $plugin_activation_blocks : [];

		// Get the list of currently active plugins
		$active_plugins = get_option('active_plugins', array());

		// Loop through the list of plugins and remove any blocked plugins
		foreach ( $this->blocked_plugins as $blocked_plugin => $path) {
			if (!isset($plugin_activation_blocks[$blocked_plugin]) || !$plugin_activation_blocks[$blocked_plugin]) {
				continue;
			}

			$index = array_search( $path, $active_plugins );
			if ( false !== $index ) {
				deactivate_plugins( $path );
			}
		}
	}
}
