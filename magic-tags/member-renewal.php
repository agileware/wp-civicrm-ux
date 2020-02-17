<?php

class Civicrm_Ux_Cf_Magic_Tag_Member_Renewal extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'member:renewal';
	}

	/**
	 * The callback function. Should return $value if no changes.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function callback( $value ) {
		$memberships = Civicrm_Ux_Membership_Utils::renewal_membership_check();
		$value       = implode( ',', [ $memberships ] );

		return $value;
	}
}