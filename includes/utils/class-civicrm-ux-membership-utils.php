<?php

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Civicrm_Ux_Membership_Utils {

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

	/**
	 * Pick the most recent membership from a list: the one with the latest end date.
	 *
	 * A current membership with no end date (a lifetime membership) counts as the latest, while
	 * a membership with no end date and no current status (such as a pending or cancelled
	 * application that never started) counts as the oldest. When memberships
	 * end on the same date, one with a current status wins over one without, so a cancelled or
	 * pending application for the same period does not hide the contact's active membership; a
	 * remaining tie goes to the higher id. This does not depend on the order the list was returned
	 * in, which is by membership id.
	 *
	 * @param array $memberships Memberships as returned by the Membership.get API, each with an
	 *                           "id", optionally an "end_date", and optionally the
	 *                           "status_id.is_current_member" flag.
	 *
	 * @return array|null The most recent membership, or null when the list is empty.
	 */
	static public function get_most_recent_membership( array $memberships ) {
		$latest = null;

		foreach ( $memberships as $membership ) {
			if ( $latest === null || self::membership_recency_key( $membership ) > self::membership_recency_key( $latest ) ) {
				$latest = $membership;
			}
		}

		return $latest;
	}

	/**
	 * Sort key for get_most_recent_membership(): the end date, then whether the status is current,
	 * then the id. The end dates are compared as strings, which orders dates written as Y-m-d
	 * correctly.
	 */
	static protected function membership_recency_key( array $membership ) {
		$is_current = ! empty( $membership['status_id.is_current_member'] );

		if ( ! empty( $membership['end_date'] ) ) {
			$end_date = $membership['end_date'];
		} else {
			// No end date. A current membership without one is a lifetime membership, which never
			// ends, so it ranks last-ending. Any other status without dates is an application that
			// never started (pending, cancelled), so it ranks lowest rather than hiding a real one.
			$end_date = $is_current ? '9999-12-31 23:59:59' : '0000-00-00 00:00:00';
		}

		return $end_date . '|' . ( $is_current ? '1' : '0' ) . '|' . str_pad( (string) ( $membership['id'] ?? 0 ), 12, '0', STR_PAD_LEFT );
	}

	/**
	 * This function is merged from
	 * agileware/agileware-civicrm-membership-summary
	 *
	 * Describes the logged in contact's most recent membership (see get_most_recent_membership()).
	 * The status, end date and summary are only filled in when that membership has a current
	 * status; the type and renewal date are filled in for any status. Nothing is described when
	 * nobody is logged in.
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

				// With no logged in contact there is no membership to describe. Without this the
				// query below has no contact filter and returns other contacts' memberships.
				$membership = null;
				if ( ! empty( $cid ) ) {
					$memberships = civicrm_api3( 'Membership', 'get', [
						'contact_id' => $cid,
						'return'     => [
							'membership_type_id.name',
							'end_date',
							'status_id.label',
							'status_id.is_current_member',
						],
						'options'    => [ 'limit' => 0 ],
					] );

					$membership = self::get_most_recent_membership( $memberships['values'] );
				}

				if ( $membership ) {

					$membership_types = $membership['membership_type_id.name'];

					// A membership with no end date (a lifetime membership) has no renewal date and
					// no end date to describe.
					$has_end_date = ! empty( $membership['end_date'] );

					if ( $has_end_date ) {
						$membership_end_date = new DateTime( $membership['end_date'] );
						$end_date_format     = CRM_Utils_Date::customFormat( $membership_end_date->format( 'Y-m-d' ) );
						$end_date_before     = new DateTime( $membership['end_date'] );
						$renewal_date        = $end_date_before->modify( '-' . (string) ( $summary_show_renewal_date ) . 'day' );
						$renewal_date_format = CRM_Utils_Date::customFormat( $renewal_date->format( 'Y-m-d' ) );
					}
					$today_date = new DateTime( 'now' );

					if ( $membership['status_id.is_current_member'] ) {
						$membership_summary_status .= $membership['status_id.label'];
						if ( ! $has_end_date ) {
							$membership_summary_message .= '<div>' . $membership_types . ' member - ' . $membership_summary_status . '</div>';
						} elseif ( $today_date < $renewal_date ) {
							$membership_summary_message .= '<div>' . $membership_types . ' member - ' . $membership_summary_status . ', next renewal date ' . $renewal_date_format . '</div>';
						} elseif ( ( $today_date >= $renewal_date ) && ( $today_date <= $membership_end_date ) ) {
							$membership_summary_message .= '<div>' . $membership_types . ' member - ' . $membership_summary_status . '. Your membership expires on ' . $end_date_format . '</div><div>' . '<a href="' . (string) ( $summary_show_renewal_URL ) . '">' . 'Click here to renew your membership' . '</a></div>';
						} elseif ( ( $today_date > $membership_end_date ) ) {
							$membership_summary_message .= '<div>' . $membership_types . ' member - ' . $membership_summary_status . '.' . $end_date_format . '</div><div>' . '<a href="' . (string) ( $summary_show_renewal_URL ) . '">' . 'Click here to renew your membership' . '</a></div>';
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
					'contact_owner.display_name',
					'contribution_recur_id',
					'contribution_recur.contribution_status_id',
					'contribution_recur.next_sched_contribution_date')
				->addJoin('Contact AS contact', 'LEFT', ['contact.id', '=', 'contact_id'])
  				->addJoin('Contact AS contact_owner', 'LEFT', ['contact_owner.id', '=', 'owner_membership_id.contact_id'])
				->addJoin('ContributionRecur AS contribution_recur', 'LEFT', ['contribution_recur.id', '=', 'contribution_recur_id'])
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
