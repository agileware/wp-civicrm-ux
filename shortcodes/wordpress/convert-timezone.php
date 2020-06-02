<?php

class Civicrm_Ux_Shortcode_Timezone extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_convert_date';
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
			'return_timezone' => '',
			'timezone'        => '',
			'return_format'   => 'd/m/Y g:ia'
		], $atts, $tag );
		try {
			$inputTimezone  = new DateTimeZone( $mod_atts['timezone'] );
			$outputTimezone = new DateTimeZone( $mod_atts['return_timezone'] );
		} catch ( Exception $exception ) {
			return 'Failed to read the timezone string';
		}
		$date = DateTime::createFromFormat( 'd/m/Y g:ia', $content, $inputTimezone );
		if ( ! $date ) {
			return 'Failed to read the time string';
		}
		$date->setTimeZone( $outputTimezone );

		return $date->format( $mod_atts['return_format'] );
	}
}