<?php

class Civicrm_Ux_Membership_Utils {

	static public function renewal_membership_check() {

		if ( self::is_civi_enable() && civicrm_initialize() ) {

			try {
				// login user or checksum provided
				$helper  = CiviCRM_Caldera_Forms::instance()->helper;
				$contact = $helper->current_contact_data_get();
				// this magic tag should return false with no contact.
				if ( ! $contact ) {
					return 0;
				}
				// Get login contact id
				$cid = $contact['id'];

				$memberships = civicrm_api3( 'Membership', 'get', [
					'contact_id' => $cid,
					'status_id'  => [ '<>' => 'Cancelled' ],
				] );

				if ( $memberships['count'] == 0 ) {
					return 0;
				} else {
					foreach ( $memberships['values'] as $membership ) {

						$membership_end_date    = new DateTime( $membership['end_date'] );
						$today_date             = new DateTime( 'now' );
						$three_month_from_today = $today_date->modify( '+3 month' );
						$is_renewal             = ( $three_month_from_today >= $membership_end_date );
						if ( $is_renewal ) {
							return $is_renewal;
							break;
						}
					}
				}
			} catch ( CiviCRM_API3_Exception $e ) {
				if ( isset( $cid ) ) {
					error_log( 'Unable to obtain membership for ' . $cid );
				}
			}
		}
	}

	// TODO move this to other utility class
	static public function is_civi_enable() {
		return function_exists( 'civicrm_initialize' );
	}

	public static function getForContact( $contact_id ) {
		$params = [
			'contact_id' => $contact_id,
			'sequential' => 1,
			'status_id'  => [ '<>' => 'Cancelled' ],
			'sort'       => 'end_date ASC',
		];

		$current = civicrm_api3( 'Membership', 'get', [
			                                              'status_id.is_current_member' => 1,
		                                              ] + $params );

		$past = civicrm_api3( 'Membership', 'get', [
			                                           'status_id.is_current_member' => 0,
			                                           'sort'                        => 'end_date DESC',
		                                           ] + $params );

		return array_merge( $current['values'], $past['values'] );
	}

	/**
	 * Get set of option values from all form price fields.
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	static public function getOptionValuesFromFields( $fields ) {
		$optionValues = [];
		foreach ( $fields as $fieldId => $field ) {
			$fieldConfig = $field['config'];
			if ( isset( $fieldConfig['auto_type'] ) && strpos( $fieldConfig['auto_type'], 'price_field' ) !== FALSE ) {
				$optionValues = array_merge( $optionValues, array_keys( $fieldConfig['option'] ) );
			}
		}

		return array_unique( $optionValues );
	}

	/**
	 * Get price field values based on given membership type and set of option
	 * values. It only returns active price set and price field values.
	 *
	 * @param $currentMembershipTypeId
	 * @param $optionValues
	 *
	 * @return array
	 * @throws CiviCRM_API3_Exception
	 */
	static public function getPriceFieldOptionsOfForm( $currentMembershipTypeId, $optionValues ) {
		$priceFieldOptions = civicrm_api3( 'PriceFieldValue', 'get', [
			'sequential'                            => 1,
			'membership_type_id'                    => $currentMembershipTypeId,
			'is_active'                             => 1,
			'price_field_id.is_active'              => 1,
			'return'                                => [
				'price_field_id.expire_on',
				'price_field_id.active_on',
			],
			'price_field_id.price_set_id.is_active' => 1,
			'id'                                    => [ 'IN' => $optionValues ],
		] );

		$priceFieldOptions = $priceFieldOptions['values'];

		return $priceFieldOptions;
	}

	/**
	 * Check if given price field value is active or not.
	 *
	 * @param $priceFieldOption
	 *
	 * @return bool
	 * @throws Exception
	 */
	static public function isPriceFieldActive( $priceFieldOption ) {
		$currentDate = new DateTime();
		$activeOn    = $priceFieldOption['price_field_id.active_on'] ?? null;
		$expireOn    = $priceFieldOption['price_field_id.expire_on'] ?? null;

		$isAfterStartDate = ( ! $activeOn ) || ( $currentDate >= DateTime::createFromFormat( 'Y-m-d H:i:s', $activeOn ) );
		$isBeforeEndDate  = ( ! $expireOn ) || ( $currentDate <  DateTime::createFromFormat( 'Y-m-d H:i:s', $expireOn ) );

		return $isBeforeEndDate && $isAfterStartDate;
	}

	static public function membership_check() {

		if ( civicrm_initialize() ) {

			try {
				// login user or checksum provided
				$helper  = CiviCRM_Caldera_Forms::instance()->helper;
				$contact = $helper->current_contact_data_get();
				// this magic tag should return false with no contact.
				if ( ! $contact ) {
					return 0;
				}
				// Get login contact id
				$cid = $contact['id'];

				$memberships = civicrm_api3( 'Membership', 'get', [
					'contact_id'                  => $cid,
					'status_id.is_current_member' => 1,
				] );

				if ( $memberships['count'] == 0 ) {
					return 0;
				} else {
					foreach ( $memberships['values'] as $membership ) {

						$membership_name = $membership['membership_name'];

						return $membership_name;
						break;
					}
				}
			} catch ( CiviCRM_API3_Exception $e ) {
				if ( isset( $cid ) ) {
					error_log( 'Unable to obtain membership for ' . $cid );
				}
			}
		}
	}

	static public function contact_subtype_check() {

		if ( self::is_civi_enable() && civicrm_initialize() ) {

			try {
				// login user or checksum provided
				$helper  = CiviCRM_Caldera_Forms::instance()->helper;
				$contact = $helper->current_contact_data_get();
				// this magic tag should return false with no contact.
				if ( ! $contact ) {
					return 0;
				}
				// Get login contact id
				$cid      = $contact['id'];
				$contacts = civicrm_api3( 'Contact', 'get', [ 'contact_id' => $cid ] );

				if ( $contacts['count'] == 0 ) {
					return 0;
				} else {
					foreach ( $contacts['values'][2]['contact_sub_type'] as $contact ) {
						if ( $contact ) {
							return $contact;
							break;
						}
					}
				}
			} catch ( CiviCRM_API3_Exception $e ) {
				if ( isset( $cid ) ) {
					error_log( 'Unable to obtain contact subtype for ' . $cid );
				}
			}
		}
	}

	/**
	 * This function is merged from
	 * agileware/agileware-civicrm-membership-summary
	 *
	 * @return array
	 * @throws Exception
	 */
	static public function get_membership_summary() {
		$membership_summary_message = '';
		$membership_types           = '';
		$membership_summary_status  = '';
		$renewal_date_format        = '';

		$opt                       = Civicrm_Ux::getInstance()
		                                       ->get_store()
		                                       ->get_option( 'civicrm_summary_options' );
		$summary_show_join_URL     = $opt['civicrm_summary_membership_join_URL'];
		$summary_show_renewal_URL  = $opt['civicrm_summary_membership_renew_URL'];
		$summary_show_renewal_date = $opt['civicrm_summary_show_renewal_date'];

		if ( civicrm_initialize() ) {

			try {

				$cid = CRM_Core_Session::singleton()->getLoggedInContactID();

				$memberships = civicrm_api3( 'Membership', 'get', [
					'contact_id' => $cid,
					'return'     => [
						'membership_type_id.name',
						'end_date',
						'status_id.label',
						'status_id.is_current_member',
					],
				] );

				foreach ( $memberships['values'] as $membership ) {

					$membership_types    = $membership['membership_type_id.name'];
					$membership_end_date = new DateTime( $membership['end_date'] );
					$end_date_format     = CRM_Utils_Date::customFormat( $membership_end_date->format( 'Y-m-d' ) );
					$end_date_before     = new DateTime( $membership['end_date'] );
					$renewal_date        = $end_date_before->modify( '-' . (string) ( $summary_show_renewal_date ) . 'day' );
					$renewal_date_format = CRM_Utils_Date::customFormat( $renewal_date->format( 'Y-m-d' ) );
					$today_date          = new DateTime( 'now' );

					if ( $membership['status_id.is_current_member'] ) {
						$membership_summary_status .= $membership['status_id.label'];
						if ( $today_date < $renewal_date ) {
							$membership_summary_message .= '<div>' . $membership_types . ' member - ' . $membership_summary_status . ', next renewal date ' . $renewal_date_format . '</div>';
							break;
						} elseif ( ( $today_date >= $renewal_date ) && ( $today_date <= $membership_end_date ) ) {
							$membership_summary_message .= '<div>' . $membership_types . ' member - ' . $membership_summary_status . '. Your membership expires on ' . $end_date_format . '</div><div>' . '<a href="' . (string) ( $summary_show_renewal_URL ) . '">' . 'Click here to renew your membership' . '</a></div>';
							break;
						} elseif ( ( $today_date > $membership_end_date ) ) {
							$membership_summary_message .= '<div>' . $membership_types . ' member - ' . $membership_summary_status . '.' . $end_date_format . '</div><div>' . '<a href="' . (string) ( $summary_show_renewal_URL ) . '">' . 'Click here to renew your membership' . '</a></div>';
							break;
						}
					}

				}

				return ( [
					$membership_summary_message,
					$membership_types,
					$membership_summary_status,
					$renewal_date_format,
					$summary_show_join_URL,
					$summary_show_renewal_URL,
				] );

			} catch ( CiviCRM_API3_Exception $e ) {

				if ( isset( $cid ) ) {

					error_log( 'Unable to obtain membership for ' . $cid );

				}

			}

		}

	}

	static public function get_all_memberships_for_contact( $queryCid = null, $types = [], $statuses = [] ) {
		$cid = $queryCid == null 
				? CRM_Core_Session::singleton()->getLoggedInContactID()
				: $queryCid;

		// If there's no contact, return null
		if ( $cid == null ) {
			return null;
		}

		$apiQuery = \Civi\Api4\Membership::get(FALSE)
				->addSelect(
					'id', 
					'membership_type_id', 
					'membership_type_id:label', 
					'membership_type_id:name', 
					'status_id', 
					'status_id:label', 
					'status_id:name', 
					'join_date',
					'start_date',
					'end_date', 
					'contact_id', 
					'contact.display_name', 
					'owner_membership_id.contact_id', 
					'contact_owner.display_name')
				->addJoin('Contact AS contact', 'LEFT', ['contact.id', '=', 'contact_id'])
  				->addJoin('Contact AS contact_owner', 'LEFT', ['contact_owner.id', '=', 'owner_membership_id.contact_id'])
				->addWhere('contact_id', '=', $cid);
		
		if ( !empty($types) ) {
			$apiQuery->addWhere('membership_type_id', 'IN', $types);
		}

		if ( !empty($statuses) ) {
			$apiQuery->addWhere('status_id:name', 'IN', $statuses);
		}
		
		$apiQuery->addOrderBy('end_date', 'ASC');

		$memberships = $apiQuery->execute();
		
		// User may have multiple memberships
		return (array) $memberships;
	}

}
