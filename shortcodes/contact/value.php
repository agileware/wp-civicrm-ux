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
			'id'          => CRM_Core_Session::singleton()->getLoggedInContactID(),
			'permission'  => 'View All Contacts',
			'bool_value'  => null,
			'id_from_url' => '',
			'field'       => '',
			'default'     => ''
		], $atts, $tag );
		$id       = $mod_atts['id'];
		if ( $mod_atts['id_from_url'] || $_GET[ $mod_atts['id_from_url'] ] ) {
			$id = $_GET[ $mod_atts['id_from_url'] ];
		}
		if ( empty( $id ) || empty( $mod_atts['field'] ) ) {
			return '(Not enough attributes)';
		}
		// check permission
		if ( ! empty( $mod_atts['permission'] ) ) {
			$permissions = explode( ',', $mod_atts['permission'] );
			if ( ! CRM_Core_Permission::check( $permissions ) ) {
				return '(permission deny)';
			}
		}

		$civi_param = [
			'return' => $mod_atts['field'],
			'id'     => $id,
		];

		try {
			$result = civicrm_api3( 'Contact', 'getvalue', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			return $e->getMessage();
		}

		// display bool value
		if ( $mod_atts['bool_value'] ) {
			$values = explode( ':', $mod_atts['bool_value'] );

			return $result ? $values[0] : $values[1];
		}

		return $this->no_value( $result ) ? $mod_atts['default'] : $result;
	}

	function no_value( $value ) {
		return empty( $value ) && $value != 0;
	}
}