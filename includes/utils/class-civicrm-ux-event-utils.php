<?php

use \Sabre\VObject;

class Civicrm_Ux_Event_Utils {
	/**
	 * @param array $opts
	 *
	 * @param bool $internal
	 *
	 * @return array|string
	 * @throws Exception
	 */
	static public function createICalObject( array $opts = [], bool $internal = false ) {
		// Header part
		$iCal = "BEGIN:VCALENDAR\r\n" .
		        "VERSION:2.0\r\n" .
		        "PRODID:-//" . get_bloginfo( "name" ) . "//NONSGML CiviEvent iCal//EN\r\n";

		$timezone_str = get_option( 'timezone_string' );
		// Generate VTIMEZONE component
		$timezone = self::generate_vtimezone( $timezone_str );
		$iCal     .= $timezone->serialize();

		$civi_param = [
			'sequential' => 1,
			'is_public'  => 1,
			'is_active'  => 1,
			'start_date' => [ '>' => 'now' ],
		];
		// Get all counted participant status
		$counted_type = [];
		try {
			$result = civicrm_api3( 'ParticipantStatusType', 'get', [
				'sequential' => 1,
				'return'     => [ 'name' ],
				'is_counted' => 1,
			] );
		} catch ( CiviCRM_API3_Exception $e ) {
			$result = [];
		}
		if ( ! empty( $result ) && ! $result['is_error'] ) {
			foreach ( $result['values'] as $type ) {
				$counted_type[] = $type['name'];
			}
		}

		// Query data from CiviCRM
		try {
			// Get the count of the query to bypass limit
			$count = civicrm_api3( 'Event', 'getcount', [
				'sequential' => 1,
				'is_public'  => 1,
				'is_active'  => 1,
			] );

			$civi_param['options'] = [ 'limit' => $count, 'sort' => 'start_date ASC' ];

			if ( $opts['types'] ) {
				$civi_param['event_type_id'] = [ 'IN' => $opts['types'] ];
			}

			// extra information for internal usage
			$civi_param['api.Participant.get'] = [
				'sequential'            => 1,
				'event_id'              => "\$value.id",
				'status_id'             => [ 'IN' => $counted_type ],
				'options'               => [ 'limit' => PHP_INT_MAX ],
				'api.Contact.getsingle' => [
					'sequential' => 1,
					'id'         => "\$value.contact_id",
				],
			];


			$result = civicrm_api3( 'Event', 'get', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			// Handle error here.
			$errorMessage = $e->getMessage();
			$errorCode    = $e->getErrorCode();
			$errorData    = $e->getExtraParams();

			var_dump( [
				'is_error'      => 1,
				'error_message' => $errorMessage,
				'error_code'    => $errorCode,
				'error_data'    => $errorData,
			] );
		}

		// Exit if error
		if ( $result['is_error'] == 1 ) {
			return 'ERROR';
		}

		$dateNow = time();
		foreach ( $result['values'] as $event ) {

			$time_str = $event['start_date'];
			$date     = DateTime::createFromFormat( 'Y-m-d H:i:s', $time_str );

			$iCal .= "BEGIN:VEVENT\r\n";

			// UID format: [date]EVENTID[id]@[site name]
			$iCal .= "UID:" .
			         'EVENTID' . $event['id'] .
			         '@' .
			         $_SERVER['SERVER_NAME'] .
			         "\r\n";

			// Local timezone
			$iCal .= 'DTSTAMP;TZID=' .
			         $timezone_str .
			         ":" .
			         $date->format( 'Ymd' ) . 'T' . $date->format( 'His' ) .
			         "\r\n";

			$iCal .= 'DTSTART;TZID=' .
			         $timezone_str .
			         ":" .
			         $date->format( 'Ymd' ) . 'T' . $date->format( 'His' ) .
			         "\r\n";

			// Check if end time set
			if ( $event['event_end_date'] == '' ) {
				$iCal .= 'DTEND;TZID=' .
				         $timezone_str .
				         ":" .
				         $date->format( 'Ymd' ) . 'T' . $date->format( 'His' ) .
				         "\r\n";
			} else {
				$end_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $event['event_end_date'] );
				$iCal     .= 'DTEND;TZID=' .
				             $timezone_str .
				             ":" .
				             $end_date->format( 'Ymd' ) . 'T' . $end_date->format( 'His' ) .
				             "\r\n";
			}

			$url  = home_url( '/' ) . 'civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Finfo&reset=1&id=' . $event['id'];

			if ($internal) {
				// Internal usage will have more information
				$count = $event['api.Participant.get']['count'];
				$iCal  .= 'SUMMARY:' .
				          $event['title'] . " - $count Participants" .
				          "\r\n";
				$iCal .= "DESCRIPTION:Event url: $url";
				if ( $count > 0 ) {
					$iCal .= '\n' . "{$count} Participants:";
					foreach ( $event['api.Participant.get']['values'] as $participant ) {
						$contact = $participant['api.Contact.getsingle'];
						$name    = $contact['display_name'];
						$email   = $contact['email'];
						$phone   = $contact['phone'];
						$iCal    .= '\n' . "{$name}, {$email}, {$phone}";
					}
				}
				$iCal .= "\r\n";
			} else {
				// External usage
				$iCal .= 'SUMMARY:' .
				         $event['title'] .
				         "\r\n";
				$iCal .= "DESCRIPTION:Event url: $url\r\n";
			}
			$iCal .= "END:VEVENT\r\n";
		}

		// Footer
		$iCal .= 'END:VCALENDAR';

		return $iCal;
	}

	/**
	 * @param $tzid
	 * @param int $from
	 * @param int $to
	 *
	 * @return bool|\Sabre\VObject\Component
	 * @throws \Exception
	 */
	static public function generate_vtimezone( $tzid, $from = 0, $to = 0 ) {
		if ( ! $from ) {
			$from = time();
		}
		if ( ! $to ) {
			$to = $from;
		}
		try {
			$tz = new \DateTimeZone( $tzid );
		} catch ( \Exception $e ) {
			return false;
		}
		// get all transitions for one year back/ahead
		$year        = 86400 * 360;
		$transitions = $tz->getTransitions( $from - $year, $to + $year );
		$vcalendar   = new VObject\Component\VCalendar();
		$vt          = $vcalendar->createComponent( 'VTIMEZONE' );
		$vt->TZID    = $tz->getName();
		$std         = null;
		$dst         = null;
		foreach ( $transitions as $i => $trans ) {
			$cmp = null;
			// skip the first entry...
			if ( $i == 0 ) {
				// ... but remember the offset for the next TZOFFSETFROM value
				$tzfrom = $trans['offset'] / 3600;
				continue;
			}
			// daylight saving time definition
			if ( $trans['isdst'] ) {
				$t_dst = $trans['ts'];
				$dst   = $vcalendar->createComponent( 'DAYLIGHT' );
				$cmp   = $dst;
			} // standard time definition
			else {
				$t_std = $trans['ts'];
				$std   = $vcalendar->createComponent( 'STANDARD' );
				$cmp   = $std;
			}
			if ( $cmp ) {
				$dt                = new DateTime( $trans['time'] );
				$offset            = $trans['offset'] / 3600;
				$cmp->DTSTART      = $dt->format( 'Ymd\THis' );
				$cmp->TZOFFSETFROM = sprintf( '%s%02d%02d', $tzfrom >= 0 ? '+' : '', floor( $tzfrom ), ( $tzfrom - floor( $tzfrom ) ) * 60 );
				$cmp->TZOFFSETTO   = sprintf( '%s%02d%02d', $offset >= 0 ? '+' : '', floor( $offset ), ( $offset - floor( $offset ) ) * 60 );
				// add abbreviated timezone name if available
				if ( ! empty( $trans['abbr'] ) ) {
					$cmp->TZNAME = $trans['abbr'];
				}
				$tzfrom = $offset;
				$vt->add( $cmp );
			}
			// we covered the entire date range
			if ( $std && $dst && min( $t_std, $t_dst ) < $from && max( $t_std, $t_dst ) > $to ) {
				break;
			}
		}
		// add X-MICROSOFT-CDO-TZID if available
		$microsoftExchangeMap = array_flip( VObject\TimeZoneUtil::$microsoftExchangeMap );
		if ( array_key_exists( $tz->getName(), $microsoftExchangeMap ) ) {
			$vt->add( 'X-MICROSOFT-CDO-TZID', $microsoftExchangeMap[ $tz->getName() ] );
		}

		return $vt;
	}
}