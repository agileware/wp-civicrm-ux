<?php

use \Sabre\VObject;

class Civicrm_Ux_REST_ICal_Feed_External extends Abstract_Civicrm_Ux_REST {

	/**
	 * @return string
	 */
	public function get_route() {
		return 'ICalFeed';
	}

	/**
	 * @return string
	 */
	public function get_endpoint() {
		return 'event';
	}

	/**
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * @param \WP_REST_Request $data
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function rest_api_callback( $data ) {
		header( 'Content-Type: text/calendar' );
		$opts = [];
		if ( $data->get_param( 'type' ) ) {
			$types         = $data->get_param( 'type' );
			$types         = explode( ',', $types );
			$opts['types'] = $types;
		}
		if ( $data->get_param( 'start_date' ) ) {
			$start_date = $data->get_param( 'start_date' );
			$opts['start_date'] = $start_date;
		}
		print Civicrm_Ux_Event_Utils::createICalObject( $opts );
		exit();
	}
}