<?php

/**
 * Class Agileware_Civicrm_Utilities_Shortcode_Campaign_Day_Remaining
 */
class Agileware_Civicrm_Utilities_Shortcode_Campaign_Days_Remaining implements iAgileware_Civicrm_Utilities_Shortcode {

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
		return 'campaign-days-remaining';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 * @throws Exception
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'id' => '',
		], $atts, $tag );
		if ( empty( $mod_atts['id'] ) ) {
			return 'Please provide the campaign id.';
		}
		$id = $mod_atts['id'];

		$civi_param = [
			'sequential'           => 1,
			'return'               => [ "name", "title", "end_date", "goal_revenue" ],
			'id'                   => $id,
			'is_active'            => 1,
			'api.Contribution.get' => [
				'sequential'  => 1,
				'campaign_id' => "\$value.id",
			],
		];

		try {
			$result = civicrm_api3( 'Campaign', 'getsingle', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			$result = [];
		}

		if ( empty( $result ) || $result['is_error'] ) {
			return 'Campaign not found.';
		}

		$end_date      = $result['end_date'];
		$day_remaining = $this->get_remaining_day( $end_date );

		if ( $day_remaining === 0 ) {
			return 'on-going';
		}

		return $day_remaining === 1 ? $day_remaining . ' day remaining' : $day_remaining . ' days remaining';
	}

	/**
	 * Get the remaining day from now
	 *
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