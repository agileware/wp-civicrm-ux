<?php

/**
 * Class Civicrm_Ux_Shortcode_Campaign_Day_Remaining
 */
class Civicrm_Ux_Shortcode_Campaign_Days_Remaining extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_campaign_days_remaining';
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
			'end-text' => 'on-going'
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
		$day_remaining = Civicrm_Ux_Campaign_Utils::get_remaining_day( $end_date );

		if ( $day_remaining === 0 ) {
			return $mod_atts['end-text'];
		}

		return $day_remaining === 1 ? $day_remaining . ' day remaining' : $day_remaining . ' days remaining';
	}
}