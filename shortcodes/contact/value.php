<?php

/**
 * Class Civicrm_Ux_Shortcode_Contact_value
 */
class Civicrm_Ux_Shortcode_Contact_value extends Abstract_Civicrm_Ux_Shortcode {
	protected $mod_atts = [];

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_contact_value';
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
		$this->mod_atts = $mod_atts = shortcode_atts( [
			'id'          => CRM_Core_Session::singleton()->getLoggedInContactID(),
			'permission'  => 'View All Contacts',
			'id_from_url' => '',
			'field'       => '',
			'check_url'   => 0,
			'default'     => ''
		], $atts, $tag );
		$id       = $mod_atts['id'];
		if ( $mod_atts['id_from_url'] && $_GET[ $mod_atts['id_from_url'] ] ) {
			$id = $_GET[ $mod_atts['id_from_url'] ];
		}
		if ( empty( $id ) || empty( $mod_atts['field'] ) ) {
			return '(Not enough attributes)';
		}
		// check permission
		if ( $id != CRM_Core_Session::singleton()->getLoggedInContactID() && ! empty( $mod_atts['permission'] ) ) {
			$permissions = explode( ',', $mod_atts['permission'] );
			if ( ! CRM_Core_Permission::check( $permissions ) ) {
				return '(permission deny)';
			}
		}

		if($mod_atts['field'] == 'address_formatted') {
			$address = CRM_Core_BAO_Address::getValues(['contact_id' => $id]);;

			return nl2br(reset($address)['display_text']);
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

		// get the label of the value if any
		try {
			$labels = civicrm_api3( 'Contact', 'getoptions', [
				'field' => $mod_atts['field'],
			] );
		} catch ( CiviCRM_API3_Exception $e ) {
			$labels = [];
		}

		if ( $labels['is_error'] ) {
			return $this->no_value( $result ) ? $mod_atts['default'] : $this->get_display_values( $result );
		}
		if ( $labels['values'] ) {
			if ( is_array( $result ) ) {
				foreach ( $result as $key => $item ) {
					$result[ $key ] = $labels['values'][ $item ] ?? $item;
				}
			} else {
				$result = $labels['values'][ $result ] ?? $result;
			}
		}

		return $this->no_value( $result ) ? $mod_atts['default'] : $this->get_display_values( $result );
	}

	function no_value( $value ) {
		return empty( $value ) && $value !== 0;
	}

	/**
	 * convert array into one string
	 *
	 * @param $values
	 *
	 * @return string
	 */
	function get_display_values( $values ) {
		if ( ! is_array( $values ) ) {
			if ( $this->mod_atts['check_url'] ) {
				if ( strpos( $values, 'http' ) !== 0 ) {
					$values = 'https://' . $values;
				}
			}
			return $values;
		}

		$content = implode( ', ', $values );

		$filtered = apply_filters('ux_contact_value/output', $content, $values, $this->mod_atts);

		return $filtered;
	}
}
