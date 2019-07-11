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
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function callback( $value ) {
		// Get login contact id
		$cid = CRM_Core_Session::singleton()->getLoggedInContactID();
		try {
			$result_membership = civicrm_api3( 'Membership', 'get', array(
				'contact_id'                   => $cid,
				'api.MembershipType.getsingle' => [ 'sequential' => 1, 'id' => "\$value.membership_type_id" ],
			) );
		} catch ( CiviCRM_API3_Exception $e ) {
			$result_membership = [];
		}

		if ( $result_membership['is_error'] == 1 ) {
			return 'No information about the user.';
		}

		$membership_type = array_pop( $result_membership['values'] )['api.MembershipType.getsingle'];
		if ( isset( $membership_type['is_error'] ) ) {
			return 'No information about the membership type';
		}

		return $membership_type['name'];
	}
}