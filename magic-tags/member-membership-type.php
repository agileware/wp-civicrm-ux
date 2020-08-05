<?php

class Civicrm_Ux_Cf_Magic_Tag_Member_Membership_Type extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'member:membership_type';
	}

	/**
	 * The callback function. Should return $value if no changes.
	 * WPCIVIUX-70
	 * 1. Current membership with the most earliest End Date (eg. If there are 3 current memberships and have end dates of Jan, March, Dec then return Jan) or
	 * 2. If no current membership exists then return the expired membership with the latest End Date (eg. If there are 3 expired memberships and have end dates of Jan, March, Dec then return Dec).
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function callback( $value ) {
		// Get login contact id
		$cid = CRM_Core_Session::singleton()->getLoggedInContactID();

		// Current memberships, soonest to expire
		$result_membership = civicrm_api3( 'Membership', 'get', [
			'contact_id'                  => $cid,
			'status_id.is_current_member' => TRUE,
			'end_date'                    => [ '>' => 'now' ],
			'options'                     => [ 'sort' => "end_date ASC" ],
			'sequential'                  => TRUE,
			'return'                      => 'membership_type_id.name',
		] );

		// Past memberships, latest to expire
		if ( $result_membership['count'] == 0 ) {
			$result_membership = civicrm_api3( 'Membership', 'get', [
				'contact_id' => $cid,
				'status_id'  => [ '<>' => 'Cancelled' ],
				'end_date'   => [ '<='  => 'now'],
				'options'    => [ 'sort' => "end_date DESC" ],
				'sequential' => TRUE,
				'return'     => 'membership_type_id.name',
			] );
		}

		// no membership at all
		if ( $result_membership['count'] == 0 ) {
			return 'No membership found for user.';
		}
		$result_membership = $result_membership['values'][0];

		if (empty($result_membership['membership_type_id.name'])) {
			// This shouldn't happen
			return json_encode($result_membership);
			// return 'No information about the membership type';
		}
		else {
			return $result_membership['membership_type_id.name'];
 		}
	}
}