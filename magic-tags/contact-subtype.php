<?php

class Civicrm_Ux_Cf_Magic_Tag_Contact_Subtype extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'contact:subtype';
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
		}
		$contact_legal = Civicrm_Ux_Membership_Utils::contact_subtype_check();
		$value         = implode( ',', [ $contact_legal ] );

		return $value;
	}
}