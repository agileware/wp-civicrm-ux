<?php

class Civicrm_Ux_Contact_Utils {
	/**
	 * Get related contact subtype
	 * Dependents on CFC enable
	 *
	 * @param int $relationshipTypeId
	 *
	 * @return |null
	 * @throws CiviCRM_API3_Exception
	 */
	static public function get_related_contact_subtype( $relationshipTypeId ) {
		$helper = CiviCRM_Caldera_Forms::instance()->helper;
		civicrm_initialize();
		$contact = $helper->current_contact_data_get();
		if ( isset( $contact ) && is_array( $contact ) ) {
			$contactId    = $contact['contact_id'];
			$relationship = civicrm_api3( 'Relationship', 'get', [
				'sequential'           => 1,
				'contact_id_a'         => $contactId,
				'contact_id_b'         => $contactId,
				'relationship_type_id' => $relationshipTypeId,
				'options'              => [ 'or' => [ [ "contact_id_a", "contact_id_b" ] ] ],
			] );
			if ( count( $relationship ) > 0 ) {
				$relationship   = $relationship['values'][0];
				$otherContactId = ( $relationship['contact_id_a'] == $contactId ) ? $relationship['contact_id_b'] : $relationship['contact_id_a'];
				$contact        = $helper->get_civi_contact( $otherContactId );

				return $contact;
			}
		}

		return null;
	}

	/**
	 * Checks the URL parameters for cid and checksum.
	 * 
	 * Logged in users are already validated, so just return their cid.
	 * 
	 */
	static public function validate_cid_and_checksum_from_url_params() {
		// If the user is logged in, we only need their cid
		// This overrides whatever is in the URL
		$loggedInCid = CRM_Core_Session::singleton()->getLoggedInContactID();
		if ( $loggedInCid ) {
			return [
				'cid' => $loggedInCid
			];
		}

		$urlParamsKeys = array_change_key_case($_GET, CASE_LOWER);
		$cid = $urlParamsKeys['cid'] ?? null;
		$cs = $urlParamsKeys['cs'] ?? null;

		// If we have no valid Contact ID and Checksum, return nothing
		if ( empty($cid) && empty($cs)) {
			return false;
		}

		if ( !empty( $cid ) && !empty( $cs ) ) {
			// Test if checksum is valid
			$isValid = Civicrm_Ux_Contact_Utils::validate_checksum( $cid, $cs );

			if ( !$isValid ) {
				return false;
			} else {
				return [
					'cid' => $cid,
					'cs' => $cs
				];
			}
		}

		// We're missing either cid or cs, and the user is not logged in.
		return false;
	}

	/**
	 * Validate the Checksum with the Contact ID in CiviCRM.
	 */
	static public function validate_checksum( $cid, $cs ) {
		$results = \Civi\Api4\Contact::validateChecksum( FALSE )
			->setContactId( $cid )
			->setChecksum( $cs )
			->execute();
		
		return $results[0]['valid'];
	}
}