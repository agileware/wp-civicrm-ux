<?php

/**
 * Class Civicrm_Ux_Shortcode_Campaign_Honour_Listing
 */

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

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
			'limit'          => 100,
		], $atts, $tag );

		// Sanitize inputs
		$id = absint($mod_atts['id'] ?? '');
		$mod_atts['display-amount'] = rest_sanitize_boolean($mod_atts['display-amount'] ?? 'false');
		$limit = absint( $mod_atts['limit'] ?? 100 ) ?: 100;

		if ( empty( $id ) ) {
			return 'Please provide the campaign id.';
		}

		// An honour listing publishes donor names to the public, so a page author must not be able
		// to point it at any campaign id they like. A site declares which campaigns may be listed
		// through this filter; by default none are, so a campaign is opted in deliberately rather
		// than exposed by default.
		$allowed_campaigns = array_map( 'intval', (array) apply_filters( 'ux_campaign_honour_listing/allowed_campaign_ids', [] ) );
		if ( ! in_array( $id, $allowed_campaigns, TRUE ) ) {
			return '';
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
				'options'                => [ 'sort' => "receive_date DESC", 'limit' => $limit ],
				'api.Contact.getsingle'  => [ 'return' => [ "display_name" ], 'id' => "\$value.contact_id" ],
			],
		];

		// A site can narrow which contributions are listed - for example to those whose donor
		// opted in to an honour roll - by adding clauses to the inner Contribution.get params.
		$civi_param['api.Contribution.get'] = apply_filters(
			'ux_campaign_honour_listing/contribution_params', $civi_param['api.Contribution.get'], $id
		);

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
			           '<div class="campaign-honour-item-contributor">' . esc_html( $name ) . '</div>' .
			           '</div>' .
			           $amount_html .
			           '</div>';
		}
		$output .= '</div>';

		return $output;
	}
}
