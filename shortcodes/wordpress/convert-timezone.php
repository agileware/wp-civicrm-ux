<?php
class Civicrm_Ux_Shortcode_Timezone extends Abstract_Civicrm_Ux_Shortcode
	/**
	 * @return string The name of shortcode
	 */
{
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
	public function shortcode_callback( $atts = [], $content = null, $tag = '' )
	{
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );
		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'return_timezone' => '',
			'timezone' => '',
		], $atts, $tag );
		try{
			date_default_timezone_set('Australia/Sydney');
			$date = DateTime::createFromFormat('d/m/Y g:ia', $content);
			if($date == false)
			{
				throw new Exception('Failed to parse date');
			}
		}catch (Exception $e)
		{echo "There is an error in your input: " . $e;}

		$t = new DateTime($date->format('d-m-Y g:ia'), new DateTimeZone($mod_atts['timezone']));
		$t->setTimeZone( new DateTimeZone($mod_atts['return_timezone']));
		return $t->format("d/m/Y g:ia");
	}
}