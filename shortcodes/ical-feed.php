<?php

use \Sabre\VObject;

/**
 * Provide shortcode for iCal feed link and generate the iCal content
 *
 * Class Agileware_Civicrm_Utilities_Shortcode_ICal_Feed
 */
class Agileware_Civicrm_Utilities_Shortcode_ICal_Feed implements iAgileware_Civicrm_Utilities_Shortcode {

	/**
	 *
	 */
	public const API_NAMESPACE = 'ICalFeed';

	/**
	 *
	 */
	public const EXTERNAL_ENDPOINT = 'event';

	public const INTERNAL_ENDPOINT = 'manage';

	public const HASH_OPTION = 'internal_ical_hash';

	/**
	 * @var \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 */
	private $manager;

	/**
	 * @param \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 *
	 * @return mixed
	 */
	public function init_setup( Agileware_Civicrm_Utilities_Shortcode_Manager $manager ) {
		$this->manager = $manager;

		$manager->get_plugin()->get_loader()->add_action( 'rest_api_init', $this, 'rest_api_init' );
		add_option( self::HASH_OPTION, '' );
		if ( empty( get_option( self::HASH_OPTION ) ) ) {
			update_option( self::HASH_OPTION, $this->manager->get_plugin()->helper->generate_hash() );
		}

		return;
	}

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ical-feed';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = NULL, $tag = '' ) {

		$url = add_query_arg( $atts, get_rest_url( NULL, '/' . self::API_NAMESPACE . '/' . self::EXTERNAL_ENDPOINT ) );

		return "<a href='$url'>iCal Feed</a>";
	}

	/**
	 * Hooked to rest_api_init
	 */
	public function rest_api_init() {
		// public (external)
		register_rest_route(
			self::API_NAMESPACE,
			self::EXTERNAL_ENDPOINT,
			[ 'methods' => 'GET', 'callback' => [ $this, 'external_api_callback' ] ] );

		// internal
		register_rest_route(
			self::API_NAMESPACE,
			self::INTERNAL_ENDPOINT,
			[ 'methods' => 'GET', 'callback' => [ $this, 'internal_api_callback' ] ] );
	}

	/**
	 * @param \WP_REST_Request $data
	 *
	 * @throws \Exception
	 */
	public function external_api_callback( WP_REST_Request $data ) {
		header( 'Content-Type: text/calendar' );
		$opts = [];
		if ( $data->get_param( 'types' ) ) {
			$types         = $data->get_param( 'types' );
			$types         = explode( ',', $types );
			$opts['types'] = $types;
		}
		print $this->createICalObject( $opts );
		exit();
	}

	public function internal_api_callback( WP_REST_Request $data ) {
		header( 'Content-Type: text/calendar' );
		$hash = $data->get_param( 'hash' );
		//		var_dump( get_option(self::HASH_OPTION) );
		if ( ! $this->manager->get_plugin()->helper->check_hash_in_option( $hash, self::HASH_OPTION ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			exit();
		}
		$opts = [];
		if ( $data->get_param( 'types' ) ) {
			$types         = $data->get_param( 'types' );
			$types         = explode( ',', $types );
			$opts['types'] = $types;
		}
		print $this->createICalObject( $opts, TRUE );
		exit();
	}

	/**
	 * @param array $opts
	 *
	 * @param bool $is_internal
	 *
	 * @return array|string
	 * @throws \Exception
	 */
	private function createICalObject( array $opts = [], bool $is_internal = FALSE ) {
		// Header part
		$iCal = "BEGIN:VCALENDAR\r\n" .
		        "VERSION:2.0\r\n" .
		        "PRODID:-//" . get_bloginfo( "name" ) . "//NONSGML CiviEvent iCal//EN\r\n";

		$timezone_str = get_option( 'timezone_string' );
		// Generate VTIMEZONE component
		$timezone = $this->generate_vtimezone( $timezone_str );
		$iCal     .= $timezone->serialize();

		$civi_param = [
			'sequential' => 1,
			'is_public'  => 1,
			'is_active'  => 1,
			'start_date' => [ '>' => "now" ],
		];
		// Query data from CiviCRM
		try {
			// Get the count of the query to bypass limit
			$count = civicrm_api3( 'Event', 'getcount', [
				'sequential' => 1,
				'is_public'  => 1,
				'is_active'  => 1,
			] );

			$civi_param['options'] = [ 'limit' => $count ];

			if ( $opts['types'] ) {
				$civi_param['event_type_id'] = [ 'IN' => $opts['types'] ];
			}

			// extra information for internal usage
			if ( $is_internal ) {
				$civi_param['api.Participant.get'] = [
					'sequential'            => 1,
					'event_id'              => "\$value.id",
					"options"               => [ "limit" => PHP_INT_MAX ],
					'api.Contact.getsingle' => [
						'sequential' => 1,
						'id'         => "\$value.contact_id",
					],
				];
			}

			$result = civicrm_api3( 'Event', 'get', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			// Handle error here.
			$errorMessage = $e->getMessage();
			$errorCode    = $e->getErrorCode();
			$errorData    = $e->getExtraParams();

			return [
				'is_error'      => 1,
				'error_message' => $errorMessage,
				'error_code'    => $errorCode,
				'error_data'    => $errorData,
			];
		}

		// Exit if error
		if ( $result['is_error'] == 1 ) {
			return 'ERROR';
		}

		$dateNow = time();
		foreach ( $result['values'] as $event ) {

			$time_str = $event['start_date'];
			// Get UTC(GMT) time
			// $timestampUTC   = get_gmt_from_date( $time_str, 'U' );
			$timestampLocal = strtotime( $time_str );


			// Check future or past
			$diff = $timestampLocal - $dateNow;
			if ( $diff < 0 ) {
				continue;
			}

			$iCal .= "BEGIN:VEVENT\r\n";

			// UID format: [date]EVENTID[id]@[site name]
			$iCal .= "UID:" .
			         date( 'Ymd', $timestampLocal ) .
			         'T230000EVENTID' .
			         $event['id'] .
			         '@' .
			         get_bloginfo( 'name' ) .
			         "\r\n";

			// Local timezone
			$iCal .= 'DTSTAMP;TZID=' .
			         $timezone_str .
			         ":" .
			         date( 'Ymd', $timestampLocal ) .
			         "T" .
			         date( 'hms', $timestampLocal ) .
			         "\r\n";

			$iCal .= 'DTSTART;TZID=' .
			         $timezone_str .
			         ":" .
			         date( 'Ymd', $timestampLocal ) .
			         "T" .
			         date( 'hms', $timestampLocal ) .
			         "\r\n";

			// Check if end time set
			if ( $event['event_end_date'] == '' ) {
				$iCal .= 'DTEND;TZID=' .
				         $timezone_str .
				         ":" .
				         date( 'Ymd', $timestampLocal ) .
				         "T" .
				         date( 'hms', $timestampLocal ) .
				         "\r\n";
			} else {
				$timestamp_end = strtotime( $event['event_end_date'] );
				$iCal          .= 'DTEND;TZID=' .
				                  $timezone_str .
				                  ":" .
				                  date( 'Ymd', $timestamp_end ) .
				                  "T" .
				                  date( 'hms', $timestamp_end ) .
				                  "\r\n";
			}

			if ( $is_internal ) {
				$count = $event['api.Participant.get']['count'];
				$iCal  .= 'SUMMARY:' .
				          $event['title'] . "-$count Participant(s)" .
				          "\r\n";

				$url  = home_url( '/' ) . 'civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Finfo&reset=1&id=' . $event['id'];
				$iCal .= "DESCRIPTION:Event url: $url";
				if ( $count > 0 ) {
					$iCal .= '\n' . "{$count} Participant(s):";
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
				$iCal .= 'SUMMARY:' .
				         $event['title'] .
				         "\r\n";
				$url  = home_url( '/' ) . '?page=CiviCRM&q=civicrm%2Fevent%2Fregister&reset=1&id=' . $event['id'];
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
	private function generate_vtimezone( $tzid, $from = 0, $to = 0 ) {
		if ( ! $from ) {
			$from = time();
		}
		if ( ! $to ) {
			$to = $from;
		}
		try {
			$tz = new \DateTimeZone( $tzid );
		} catch ( \Exception $e ) {
			return FALSE;
		}
		// get all transitions for one year back/ahead
		$year        = 86400 * 360;
		$transitions = $tz->getTransitions( $from - $year, $to + $year );
		$vcalendar   = new VObject\Component\VCalendar();
		$vt          = $vcalendar->createComponent( 'VTIMEZONE' );
		$vt->TZID    = $tz->getName();
		$std         = NULL;
		$dst         = NULL;
		foreach ( $transitions as $i => $trans ) {
			$cmp = NULL;
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