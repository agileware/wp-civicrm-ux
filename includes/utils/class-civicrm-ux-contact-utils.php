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
}