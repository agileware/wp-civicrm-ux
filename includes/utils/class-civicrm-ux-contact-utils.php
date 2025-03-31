<?php

class Civicrm_Ux_Contact_Utils {

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

		// We're missing cid, cs or both, and the user is not logged in.
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