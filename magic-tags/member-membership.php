<?php

class Civicrm_Ux_Cf_Magic_Tag_Member_Membership extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'member:membership';
	}

	/**
	 * The callback function. Should return $value if no changes.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function callback( $value ) {
		if ( ! is_user_logged_in() ) {
			return $value;
		} else {
			$membership = Civicrm_Ux_Membership_Utils::membership_check();
			$value      = implode( ',', [ $membership ] );

		}

		return $value;
	}
}