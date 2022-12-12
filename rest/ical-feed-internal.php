<?php

use \Sabre\VObject;

class Civicrm_Ux_REST_ICal_Feed_Internal extends Abstract_Civicrm_Ux_REST {
	const HASH_OPTION = 'internal_ical_hash';

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
		return 'manage';
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
		$hash = $data->get_param( 'hash' );
		//		var_dump( get_option(self::HASH_OPTION) );
		if ( ! $this->manager->get_plugin()->helper->check_hash_in_option( $hash, self::HASH_OPTION ) ) {
			header( 'HTTP/1.1 404 Not Found' );
			exit();
		}
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
		print Civicrm_Ux_Event_Utils::createICalObject( $opts, true );
		exit();
	}
}