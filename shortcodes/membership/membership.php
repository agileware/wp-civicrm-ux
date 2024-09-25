<?php

class Civicrm_Ux_Shortcode_Membership extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_membership';
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
		$atts = shortcode_atts( [
			'type' => '',
		], $atts, $tag );

        $args = [];

		$contactAuth = Civicrm_Ux_Contact_Utils::validate_cid_and_checksum_from_url_params();

		// If we have an invalid contact, abort
		if ( !$contactAuth ) {
			// Display an error message
			return '<p>You do not have access to this page.</p>';
		}

        // Buffer the output
        ob_start();

        // Process the inner shortcodes and content
        $content = do_shortcode( $content );

        civicrm_ux_load_template_part( 'shortcode', 'membership', array_merge($args, $contactAuth, ['content' => $content]) );
        
        return ob_get_clean();
	}
}
