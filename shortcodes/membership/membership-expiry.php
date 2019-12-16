<?php

class Civicrm_Ux_Shortcode_Membership_Expiry extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_membership_expiry';
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
		$current_membership_statuses = [];

		$membership_expiry_message = '';

		if ( civicrm_initialize() ) {

			try {

				$cid = CRM_Core_Session::singleton()->getLoggedInContactID();

				$memberships = civicrm_api3( 'Membership', 'get', array( 'contact_id' => $cid, 'return' => [ 'end_date', 'status_id.is_current_member', 'membership_type_id.name' ] ) );

				foreach ( $memberships["values"] as $membership ) {

					if ( $membership['status_id.is_current_member'] ) {
						// TODO - Include the membership type with the expiry message

						$membership_end_date = CRM_Utils_Date::customFormat($membership['end_date']);

						$membership_expiry_message .= '<div>' . 'Your membership expires on ' . $membership_end_date . '</div>';

						break;

					}

				}

				return $membership_expiry_message;

			} catch ( CiviCRM_API3_Exception $e ) {

				if ( isset( $cid ) ) {

					error_log( 'Unable to obtain membership for ' . $cid );

				}

			}

		}
	}
}
