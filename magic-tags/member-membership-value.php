<?php

class Civicrm_Ux_Cf_Magic_Tag_Member_Membership_Value extends Abstract_Civicrm_Ux_Cf_Magic_Tag {

	/**
	 * The tag name
	 *
	 * @return string
	 */
	function get_tag_name() {
		return 'member:membership_value';
	}

	/**
	 * The callback function. Should return $value if no changes.
	 *
	 * @param string $value
	 *
	 * @return string
	 * @throws CiviCRM_API3_Exception
	 * @throws Exception
	 */
	function callback( $value ) {
		global $civicrmForm;

		// Initializing CiviCRM.
		civi_wp()->initialize();

		// Get logged in Contact Id
		$loggedInContact = CRM_Core_Session::singleton()->getLoggedInContactID();

		if ( $loggedInContact ) {

			// Get all current membership statues.
			$membershipStatues = Civicrm_Ux_Membership_Utils::getCurrentMembershipStatues();

			if ( count( $membershipStatues ) ) {

				// Get current memberships of logged in contact
				$memberships = Civicrm_Ux_Membership_Utils::getMembershipsByStatues( $membershipStatues );

				if ( count( $memberships ) ) {

					// Consider only first membership and its type.
					$currentMembership       = $memberships[0];
					$currentMembershipTypeId = $currentMembership['membership_type_id'];

					// Go through all fields and check if any of them using Priceset.
					$fields = $civicrmForm['fields'];

					// Get all possible option values of the form.
					$optionValues = Civicrm_Ux_Membership_Utils::getOptionValuesFromFields( $fields );

					if ( count( $optionValues ) ) {

						// get price field options of selected membership type and option fields.
						$priceFieldOptions = Civicrm_Ux_Membership_Utils::getPriceFieldOptionsOfForm( $currentMembershipTypeId, $optionValues );

						if ( count( $priceFieldOptions ) ) {
							foreach ( $priceFieldOptions as $priceFieldOption ) {

								// If option field is active return the id.
								if ( Civicrm_Ux_Membership_Utils::isPriceFieldActive( $priceFieldOption ) ) {
									return $priceFieldOption['id'];
								}
							}
						}
					}
				}
			}
		}

		return $value;
	}
}