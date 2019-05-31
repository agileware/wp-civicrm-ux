<?php

use \Sabre\VObject;

/**
 * Provide shortcode for iCal feed link and generate the iCal content
 *
 * Class Agileware_Civicrm_Utilities_Shortcode_ICal_Feed
 */
class Agileware_Civicrm_Utilities_Shortcode_ICal_Feed implements iAgileware_Civicrm_Utilities_Shortcode {

	/**
	 * @var \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 */
	private $manager;

	/**
	 *
	 */
	private const API_NAMESPACE = 'ICalFeed';

	/**
	 *
	 */
	private const API_ENDPOINT = 'event';

	/**
	 * @param \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 *
	 * @return mixed
	 */
	public function init_setup( $manager ) {
		$this->manager = $manager;
		$manager->get_plugin()->get_loader()->add_action( 'rest_api_init', $this, 'rest_api_init' );
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
		$url = get_rest_url( NULL, '/' . self::API_NAMESPACE . '/' . self::API_ENDPOINT );

		return "<a href='$url'>iCal Feed</a>";
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

	/**
	 * Hooked to rest_api_init
	 */
	public function rest_api_init() {
		register_rest_route(
			self::API_NAMESPACE,
			'event',
			[ 'method' => 'GET', 'callback' => [ $this, 'api_callback' ] ] );
	}

	/**
	 * @param $data
	 *
	 * @throws \Exception
	 */
	public function api_callback( $data ) {
		civicrm_initialize();
		header( 'Content-Type: text/calendar' );
		print $this->createICalObject();
		exit();
	}

	/**
	 * @param array $opts
	 *
	 * @return array|string
	 * @throws \Exception
	 */
	private function createICalObject( $opts = [] ) {
		// Header part
		$iCal = "BEGIN:VCALENDAR\r\n" .
		        "VERSION:2.0\r\n" .
		        "PRODID:-//" . get_bloginfo( "name" ) . "//NONSGML CiviEvent iCal//EN\r\n";

		$timezone_str = get_option( 'timezone_string' );
		// Generate VTIMEZONE component
		$timezone = $this->generate_vtimezone( $timezone_str );
		$iCal     .= $timezone->serialize();

		// Query data from CiviCRM
		try {
			// Get the count of the query to bypass limit
			$count = civicrm_api3( 'Event', 'getcount', [
				'sequential' => 1,
				'is_public'  => 1,
				'is_active'  => 1,
			] );

			$result = civicrm_api3( 'Event', 'get', [
				'sequential' => 1,
				'is_public'  => 1,
				'is_active'  => 1,
				'options'    => [ 'limit' => $count ],
			] );
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

			$iCal .= 'SUMMARY:' .
			         $event['title'] .
			         "\r\n" .
			         "END:VEVENT\r\n";
		}

		// Footer
		$iCal .= 'END:VCALENDAR';

		return $iCal;
	}
}