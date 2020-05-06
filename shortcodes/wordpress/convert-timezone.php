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
		$date = DateTime::createFromFormat('d/m/Y g:ia', $content);
		try{
			$t = new DateTime($date->format('d-m-Y g:ia'), new DateTimeZone($mod_atts['timezone']));
			$t->setTimeZone( new DateTimeZone($mod_atts['return_timezone']));
			return $t->format("d/m/Y g:ia");

		}catch(Exception $exc){
			return 'Fatal exception catched: '.$exc->getMessage();
			exit(1);
		}catch (Error $err) {
			return 'There is an error with the input given: '.$err->getMessage();
			exit(1);
		}
	}
}