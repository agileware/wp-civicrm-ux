<?php

/**
 * Class Agileware_Civicrm_Utilities_Shortcode_Campaign_Day_Remaining
 */
class Agileware_Civicrm_Utilities_Shortcode_Campaign_Day_Remaining implements iAgileware_Civicrm_Utilities_Shortcode {

	/**
	 * @var \Agileware_Civicrm_Utilities_Shortcode_Manager
	 */
	private $manager;

	/**
	 * @param \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 *
	 * @return mixed
	 */
	public function init_setup( Agileware_Civicrm_Utilities_Shortcode_Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'campaign-day-remaining';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = NULL, $tag = '' ) {
		if ( empty( $atts ) || ! $atts['id'] ) {
			return 'Please provide the campaign id.';
		}
		$id = $atts['id'];

		$civi_param = [
			'sequential'           => 1,
			'return'               => [ "name", "title", "end_date", "goal_revenue" ],
			'id'                   => $id,
			'is_active'            => 1,
			'api.Contribution.get' => [ 'sequential' => 1, 'campaign_id' => "\$value.id" ],
		];

		try {
			$result = civicrm_api3( 'Campaign', 'getsingle', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			$result = [];
		}

		if ( empty( $result ) || $result['is_error'] ) {
			return 'Campaign not found.';
		}

		$end_date = $result['end_date'];
		$day_remaining         = $this->get_remaining_day( $end_date );

		return $day_remaining;
	}

	/**
	 * Get the remaining day from now
	 * @param string $end_date
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function get_remaining_day( $end_date ) {
		if ( empty( $end_date ) ) {
			return 0;
		}

		$end = DateTime::createFromFormat( 'Y-m-d H:i:s', $end_date );
		$now = new DateTime( 'now' );

		if ( $now > $end ) {
			return 0;
		}

		$interval = $now->diff( $end );

		return $interval->d;
	}
}