<?php

/**
 * Class Civicrm_Ux_Shortcode_Wordpress_CF_Value
 */
class Civicrm_Ux_Shortcode_Wordpress_CF_Value extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_cf_value';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'type'    => 'post',
			'id'      => get_the_ID(),
			'field'   => '',
			'single'  => true,
			'default' => ''
		], $atts, $tag );
		if ( empty( $mod_atts['id'] ) || empty( $mod_atts['field'] ) ) {
			return '(Not enough attributes)';
		}

		$result = get_metadata( $mod_atts['type'], $mod_atts['id'], $mod_atts['field'], $mod_atts['single'] );

		return $result;
	}
}