<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/public
 */

use Civi\Api4\Event;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/public
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux_Public {

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
	 * @param string $civicrm_ux The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $civicrm_ux, $version ) {

		$this->civicrm_ux = $civicrm_ux;
		$this->version    = $version;

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
		 * defined in Civicrm_Ux_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Civicrm_Ux_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->civicrm_ux, plugin_dir_url( __FILE__ ) . 'css/civicrm-ux-public.css', [], $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		global $post;
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

		wp_enqueue_script( $this->civicrm_ux, plugin_dir_url( __FILE__ ) . 'js/civicrm-ux-public.js', [ 'jquery' ], $this->version, FALSE );

		// Only enqueue this script if cancel_event_registration is present
		if ( $post && has_shortcode( $post->post_content, 'ux_event_cancelregistration' ) && has_shortcode( $post->post_content, 'ux_event_cancelregistration_button' ) ) {
			wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [] );
			wp_enqueue_script( 'event_cancel_registration', plugin_dir_url( __FILE__ ) . 'js/event_cancel_registration.js', array('jquery'), $this->version, true );
		}

		// Only enqueue this script if event_markattendance is present
		if ( $post && has_shortcode( $post->post_content, 'ux_event_markattendance' ) && has_shortcode( $post->post_content, 'ux_event_markattendance_button' ) ) {
			wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [] );
			wp_enqueue_script( 'event_mark_attendance', plugin_dir_url( __FILE__ ) . 'js/event_mark_attendance.js', array('jquery'), $this->version, true );
		}

		// Only enqueue this script if an element with the id "wp-civicrm-ux-filter" is present
		if ( $post && strpos( $post->post_content, 'id="wp-civicrm-ux-filter-form"' ) !== false ) {
			wp_enqueue_script( 'wp-civicrm-ux-filter', plugin_dir_url( __FILE__ ) . '/js/wp-civicrm-ux-filter.js', array('jquery'), $this->version, true );
		}

		$opt_contribution_ux = get_option( 'civicrm_contribution_ux', [] );

		$options_map = [
			'is_recur_default'     => isset($opt_contribution_ux['is_recur_default']),
			'is_autorenew_default' => isset($opt_contribution_ux['is_autorenew_default']),
		];

		wp_add_inline_script( $this->civicrm_ux, 'window.wp = window.wp || ({}); window.wp.CiviCRM_UX = (' . json_encode( $options_map ) . ')', 'before' );
	}

	/**
	 * Use the title as set for WordPress in the Avada page titlebar.
	 *
	 * @since 1.1.6
	 */
	public function avada_page_title_bar_contents( $parts ) {
		[ $title, $subtitle, $secondary_content ] = $parts;

		return [ get_the_title(), $subtitle, $secondary_content ];
	}

	/**
	 * Override the timezone of Event Organiser event date/times to that of the
	 * linked event.
	 *
	 * @since 1.2.0
	 */
	function event_organiser_timezone_filter( $formatted, \DateTime $date, $format, $post_id, $occurrence_id ) {
		$ceo = civicrm_eo();
		if ( method_exists( $ceo->db, 'get_civi_event_ids_by_eo_event_id' ) ) {
			$civi_id = reset( $ceo->db->get_civi_event_ids_by_eo_event_id( $post_id ) );
		} else {
			$civi_id = reset( $ceo->mapping->get_civi_event_ids_by_eo_event_id( $post_id ) );
		}

		try {
			$civi_event = $civi_id ? ( Event::get( FALSE )
			                                           ->addSelect( 'id', 'event_tz' )
			                                           ->addWhere( 'id', '=', $civi_id )
			                                           ->execute() )[0] : NULL;

			if ( ! empty( $civi_event['event_tz'] ) ) {
				$timezone = new \DateTimeZone( $civi_event['event_tz'] );

				$date->setTimeZone( $timezone );

				return eo_format_datetime( $date, $format );
			}
		} catch ( \API_Exception $e ) {
			\Civi::log()
			     ->error( "Could not set timezone for event {$post_id}: {$e->getMessage()}" );
		}

		return $formatted;
	}

}
