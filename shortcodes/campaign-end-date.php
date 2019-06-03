<?php

/**
 * Class Agileware_Civicrm_Utilities_Shortcode_Campaign_End_date
 */
class Agileware_Civicrm_Utilities_Shortcode_Campaign_End_date implements iAgileware_Civicrm_Utilities_Shortcode {

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
		return 'campaign-end-date';
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
		$end_date = DateTime::createFromFormat( 'Y-m-d H:i:s', $end_date )->format( 'j F Y');
		return $end_date;
	}
}