<?php

class Civicrm_Ux_Cf_Magic_Tag_User_Roles extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'user:roles';
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
			$user = get_userdata( get_current_user_id() );

			$value = implode( ',', $user->roles );
		}

		return $value;
	}
}