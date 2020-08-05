<?php

/**
 * Class Civicrm_Ux_Cf_Magic_Tag_Member_Membership_Id
 *
 * This magic tag is suppose to return the linked price field value id, not the membership id
 * The membership id should be passed in in the query string parameter called mid
 * Only one active price set with membership can be used in the form
 */
class Civicrm_Ux_Cf_Magic_Tag_Member_Membership_Id extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'member:membership_id';
	}

	/**
	 * The callback function. Should return $value if no changes.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	function callback( $value ) {
		$value = '0';
		$cid = CRM_Core_Session::singleton()->getLoggedInContactID();
		$mid = CRM_Utils_Request::retrieve( 'mid', 'String' );
		if ( empty( $cid ) || empty( $mid ) ) {
			return $value;
		}
		// check if the membership is the right one
		$membership = NULL;
		try {
			$membership = civicrm_api3( 'Membership',
				'getsingle',
				[
					'id' => $mid,
					'status_id' => ['NOT IN' => ["Cancelled"]],
					'contact_id' => $cid
				] );
		} catch (Exception $e) {
			return $value;
		}
		// get the price set used in the form
		$optionValues = Civicrm_Ux_Membership_Utils::getOptionValuesFromFields( Civicrm_Ux_Cf_Magic_Tag_Manager::form()['fields'] );
		if ( !count( $optionValues ) ) {
			return $value;
		}
		try {
			$priceFieldOptions = Civicrm_Ux_Membership_Utils::getPriceFieldOptionsOfForm( $membership['membership_type_id'], $optionValues );
		} catch ( CiviCRM_API3_Exception $e ) {
			return $value;
		}
		foreach ($priceFieldOptions as $option) {
			try {
				if ( Civicrm_Ux_Membership_Utils::isPriceFieldActive( $option ) ) {
					return $option['id'];
				}
			} catch ( Exception $e ) {
				return $value;
			}
		}
		return $value;
	}
}