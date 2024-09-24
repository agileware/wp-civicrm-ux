<?php

class Civicrm_Ux_Shortcode_Membership_Row extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_membership_row';
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

		if ( isset($atts['membership_type']) ) {
			$atts['membership_type'] = explode(',', $atts['membership_type']);
		}

		if ( isset($atts['membership_status']) ) {
			$atts['membership_status'] = explode(',', $atts['membership_status']);
		}

        // override default attributes with user attributes
		$atts = shortcode_atts( [
			'membership_type' => [],
			'membership_status' => [],
			'expiry_date' => '',
			'renewal_url' => ''
		], $atts, $tag );

        $args = [
            'content' => $content,
			'expiry_date' => $atts['expiry_date'],
			'renewal_url' => $atts['renewal_url']
        ];

		$cid = null; // FOR TESTING

		$memberships = Civicrm_Ux_Membership_Utils::get_all_memberships_for_contact( $cid, $atts['membership_type'], $atts['membership_status'], $atts['expiry_date'] );

        // Buffer the output
        ob_start();

        civicrm_ux_load_template_part( 'shortcode', 'membership-row', array_merge( $args, ['memberships' => $memberships] ) );
        
        return ob_get_clean();
	}
}
