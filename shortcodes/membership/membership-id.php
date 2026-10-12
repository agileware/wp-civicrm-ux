<?php

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

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
		$logged_in_contact = CRM_Core_Session::singleton()->getLoggedInContactID();

		$mod_atts = shortcode_atts( [
			'type' => '',
			'status' => '',
			'contact_id' => $logged_in_contact,
		], $atts, $tag );

		// Sanitize inputs
		$mod_atts['type'] = sanitize_text_field($mod_atts['type']);
		$mod_atts['status'] = sanitize_text_field($mod_atts['status']);
		$mod_atts['contact_id'] = absint($mod_atts['contact_id']);

		// contact_id defaults to the current contact. Reading another contact's membership id
		// through the attribute is an enumeration oracle, so it is only honoured for a user who
		// may view all contacts in CiviCRM; otherwise fall back to the current contact.
		if ( $mod_atts['contact_id'] !== absint( $logged_in_contact ) && ! CRM_Core_Permission::check( 'view all contacts' ) ) {
			$mod_atts['contact_id'] = absint( $logged_in_contact );
		}

		if ( empty( $mod_atts['contact_id'] ) ) {
			return 'not found';
		}

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