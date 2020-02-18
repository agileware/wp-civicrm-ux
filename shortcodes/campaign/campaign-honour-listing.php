<?php

/**
 * Class Civicrm_Ux_Shortcode_Campaign_Honour_Listing
 */
class Civicrm_Ux_Shortcode_Campaign_Honour_Listing extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_campaign_honour_listing';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 * @throws \CRM_Core_Exception
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'id'             => '',
			'display-amount' => false,
		], $atts, $tag );
		if ( empty( $mod_atts['id'] ) ) {
			return 'Please provide the campaign id.';
		}
		$id = $mod_atts['id'];

		if ( $mod_atts['display-amount'] === 'false' ) {
			$mod_atts['display-amount'] = false;
		}

		$civi_param = [
			'sequential'           => 1,
			'return'               => [ "name", "title", "end_date", "goal_revenue" ],
			'id'                   => $id,
			'is_active'            => 1,
			'api.Contribution.get' => [
				'sequential'             => 1,
				'campaign_id'            => "\$value.id",
				'contribution_status_id' => "Completed",
				'options'                => [ 'sort' => "receive_date DESC", 'limit' => 100 ],
				'api.Contact.getsingle'  => [ 'return' => [ "display_name" ], 'id' => "\$value.contact_id" ],
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

		$output = '<div class="campaign-honour-listing">';
		foreach ( $result['api.Contribution.get']['values'] as $contribution ) {
			$name   = $contribution['api.Contact.getsingle']['display_name'];
			$amount = $contribution['total_amount'];

			// Conditional display amount
			$amount_html = '';
			if ( ! empty( $amount ) && $mod_atts['display-amount'] ) {
				$amount_html = '<div class="campaign-honour-item-amount">' . CRM_Utils_Money::format( $amount ) . '</div>';
			}

			$output .= '<div class="campaign-honour-item">' .
			           '<div class="campaign-honour-item-info">' .
			           '<div class="campaign-honour-item-contributor">' . $name . '</div>' .
			           '</div>' .
			           $amount_html .
			           '</div>';
		}
		$output .= '</div>';

		return $output;
	}
}
