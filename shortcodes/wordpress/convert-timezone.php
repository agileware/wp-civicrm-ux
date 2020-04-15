<?php
class Civicrm_Ux_Shortcode_Timezone extends Abstract_Civicrm_Ux_Shortcode
{
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'convert_timezone';
	}
	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' )
	{
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'return_timezone' => '',
		], $atts, $tag );

		$t = new DateTime($content, new DateTimeZone('Australia/Sydney'));
		$t->setTimeZone( new DateTimeZone($mod_atts['return_timezone']));
		return $t->format("m/d/Y g:ia");
	}
}
