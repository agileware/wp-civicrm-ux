<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/public
 * @author     Your Name <email@example.com>
 */
class Agileware_Civicrm_Utilities_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $agileware_civicrm_utilities    The ID of this plugin.
	 */
	private $agileware_civicrm_utilities;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $agileware_civicrm_utilities       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $agileware_civicrm_utilities, $version ) {

		$this->agileware_civicrm_utilities = $agileware_civicrm_utilities;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->agileware_civicrm_utilities, plugin_dir_url( __FILE__ ) . 'css/agileware-civicrm-utilities-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->agileware_civicrm_utilities, plugin_dir_url( __FILE__ ) . 'js/agileware-civicrm-utilities-public.js', array( 'jquery' ), $this->version, false );

	}

}
