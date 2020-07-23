<?php

/**
 * Class Civicrm_Ux_Cf_Magic_Tag_Member_Current
 *
 * Implements a short code which returns true if the user is a current member,
 * or the empty string otherwise
 */
class Civicrm_Ux_Cf_Magic_Tag_Member_Current extends Abstract_Civicrm_Ux_Cf_Magic_Tag {
	public function get_tag_name() {
		return 'member:current';
	}

	public function callback( $value ) {
		$helper  = CiviCRM_Caldera_Forms::instance()->helper;
		$contact = $helper->current_contact_data_get();

		$memberships = civicrm_api3('Membership', 'getcount', [
			'contact_id' => $contact['id'],
			'status_id.is_current_member' => TRUE,
		]);

		return ($memberships > 0) ? 'true' : '';
	}
}