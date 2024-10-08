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

		if ( !empty($atts['membership_type']) ) {
			$atts['membership_type'] = explode(',', $atts['membership_type']);
		}

		if ( !empty($atts['membership_status']) ) {
			$atts['membership_status'] = explode(',', $atts['membership_status']);
		}

        // override default attributes with user attributes
		$atts = shortcode_atts( [
			'membership_type' => [],
			'membership_status' => [],
			'expiration_offset' => '',
			'renewal_url' => ''
		], $atts, $tag );

        $args = [
            'content' => $content,
			'expiration_offset' => $atts['expiration_offset'],
			'renewal_url' => $atts['renewal_url']
        ];

		$contactAuth = Civicrm_Ux_Contact_Utils::validate_cid_and_checksum_from_url_params();

		// If we have an invalid contact, abort
		if ( !$contactAuth ) {
			return;
		}

		$memberships = Civicrm_Ux_Membership_Utils::get_all_memberships_for_contact( $contactAuth['cid'], $atts['membership_type'], $atts['membership_status'] );

        // Buffer the output
        ob_start();

        civicrm_ux_load_template_part( 'shortcode', 'membership-row', array_merge( $args, $contactAuth, ['memberships' => $memberships] ) );
        
        return ob_get_clean();
	}
}
