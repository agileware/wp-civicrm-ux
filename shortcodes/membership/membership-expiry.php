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

				$memberships = civicrm_api3( 'Membership', 'get', array( 'contact_id' => $cid ) );

				$membership_status_list = civicrm_api3( 'MembershipStatus', 'get', array() );

				//If membership status gives a current membership, add it to the array
				foreach ( $membership_status_list['values'] as $membership_status ) {

					if ( $membership_status['is_current_member'] ) {

						array_push( $current_membership_statuses, $membership_status['id'] );

					}

				}

				foreach ( $memberships["values"] as $membership ) {

					$membership_end_date = new DateTime( $membership['end_date'] );

					if ( in_array( (int) $membership['status_id'], $current_membership_statuses ) ) {

						$membership_expiry_message .= '<div>' . 'Your membership expires on ' . $membership_end_date->format( 'F j, Y' ) . '</div>';

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