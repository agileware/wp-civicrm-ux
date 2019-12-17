<?php

class Civicrm_Ux_Shortcode_Membership_Id extends Abstract_Civicrm_Ux_Shortcode{

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_membership_id';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 * @throws Exception
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		$mod_atts = $mod_atts = shortcode_atts( [
			'type' => '',
			'status' => '',
			'cid' => CRM_Core_Session::singleton()->getLoggedInContactID(),
		], $atts, $tag );
		$params = array_filter( $mod_atts, function ( $value ) {
			return !empty($value) || $value === 0;
		});
		$membership = civicrm_api3( 'Membership', 'get', $params );
		if ( $membership['count'] ) {
			$membership = array_shift( $membership['values'] );

			return $membership['id'];
		}
		return 'not found';
	}
}