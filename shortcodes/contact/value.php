<?php

/**
 * Class Civicrm_Ux_Shortcode_Contact_value
 */
class Civicrm_Ux_Shortcode_Contact_value extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'contact-value';
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
			'id'      => CRM_Core_Session::singleton()->getLoggedInContactID(),
			'field'   => '',
			'default' => ''
		], $atts, $tag );
		if ( empty( $mod_atts['id'] ) || empty( $mod_atts['field'] ) ) {
			return '(Not enough attributes)';
		}
		$id = $mod_atts['id'];

		$civi_param = [
			'return' => $mod_atts['field'],
			'id'     => $id,
		];

		try {
			$result = civicrm_api3( 'Contact', 'getvalue', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			return $e->getMessage();
		}

		return $result;
	}
}