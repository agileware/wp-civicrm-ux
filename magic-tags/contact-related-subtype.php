<?php

class Civicrm_Ux_Cf_Magic_Tag_Contact_Related_Subtype extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'contact:related_subtype';
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
		$related = Civicrm_Ux_Contact_Utils::get_related_contact_subtype( 11 );
		if ( ! empty( $related ) ) {
			$value = $related['contact_sub_type'][0];
		} else {
			$value = "NOT FOUND";
		}

		return $value;
	}
}