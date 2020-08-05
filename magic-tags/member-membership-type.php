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

		$result_membership = civicrm_api3( 'Membership', 'get', [
			'contact_id'                   => $cid,
			'status_id'                    => [ 'IN' => [ "Current", "New" ] ],
			'options'                      => [ 'sort' => "end_date ASC" ],
			'api.MembershipType.getsingle' => [ 'sequential' => 1, 'id' => "\$value.membership_type_id" ],
		] );

		// not current membership
		if ( $result_membership['count'] == 0 ) {
			$result_membership = civicrm_api3( 'Membership', 'get', [
				'contact_id'                   => $cid,
				'options'                      => [ 'sort' => "end_date DESC" ],
				'api.MembershipType.getsingle' => [ 'sequential' => 1, 'id' => "\$value.membership_type_id" ],
			] );
		}

		// no membership at all
		if ( $result_membership['count'] == 0 ) {
			return 'No membership found the user.';
		}

		$membership_type = reset( $result_membership['values'] )['api.MembershipType.getsingle'];
		if ( isset( $membership_type['is_error'] ) ) {
			return 'No information about the membership type';
		}

		return $membership_type['name'];
	}
}