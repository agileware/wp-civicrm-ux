<?php

use \Sabre\VObject;

/**
 * Provide shortcode for iCal feed link and generate the iCal content
 *
 * Class Agileware_Civicrm_Utilities_Shortcode_ICal_Feed
 */
class Agileware_Civicrm_Utilities_Shortcode_ICal_Feed implements iAgileware_Civicrm_Utilities_Shortcode {

	const HASH_OPTION = 'internal_ical_hash';

	const API_NAMESPACE = 'ICalFeed';

	const EXTERNAL_ENDPOINT = 'event';

	const INTERNAL_ENDPOINT = 'manage';

	/**
	 * @var \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 */
	private $manager;

	/**
	 * @param \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 *
	 * @return mixed
	 */
	public function init_setup( Agileware_Civicrm_Utilities_Shortcode_Manager $manager ) {
		$this->manager = $manager;

		add_option( self::HASH_OPTION, '' );
		if ( empty( get_option( self::HASH_OPTION ) ) ) {
			update_option( self::HASH_OPTION, $this->manager->get_plugin()->helper->generate_hash() );
		}

		return;
	}

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ical-feed';
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

		$url = add_query_arg( $mod_atts, get_rest_url( null, '/' . self::API_NAMESPACE . '/' . self::EXTERNAL_ENDPOINT ) );

		return "<a href='$url'>" . ( empty( $content ) ? 'iCal Feed' : $content ) . "</a>";
	}
}