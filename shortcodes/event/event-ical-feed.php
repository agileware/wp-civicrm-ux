<?php

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use \Sabre\VObject;

/**
 * Provide shortcode for iCal feed link and generate the iCal content
 *
 * Class Civicrm_Ux_Shortcode_Event_ICal_Feed
 */
class Civicrm_Ux_Shortcode_Event_ICal_Feed extends Abstract_Civicrm_Ux_Shortcode {

	const HASH_OPTION = 'internal_ical_hash';

	const API_NAMESPACE = 'ICalFeed';

	const EXTERNAL_ENDPOINT = 'event';

	const INTERNAL_ENDPOINT = 'manage';

	public function __construct( $manager ) {
		parent::__construct( $manager );
		// TODO move this part
		add_option( self::HASH_OPTION, '' );
		if ( empty( get_option( self::HASH_OPTION ) ) ) {
			update_option( self::HASH_OPTION, $this->manager->get_plugin()->helper->generate_hash() );
		}
	}

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_ical_feed';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'type' => '',
		], $atts, $tag );

		// type is a comma list of event type labels; reduce it to the characters those can contain
		// so it cannot carry quotes or markup into the link below.
		$mod_atts['type'] = preg_replace( '/[^A-Za-z0-9 ,_-]/', '', (string) $mod_atts['type'] );

		$url = add_query_arg( array_map( 'rawurlencode', $mod_atts ), get_rest_url( null, '/' . self::API_NAMESPACE . '/' . self::EXTERNAL_ENDPOINT ) );

		return '<a href="' . esc_url( $url ) . '">' . ( empty( $content ) ? 'iCal Feed' : wp_kses_post( $content ) ) . '</a>';
	}
}