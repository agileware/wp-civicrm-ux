<?php

/**
 * Shortcode 'ux_activity_listing'
 * Accept parameters:
 * 'type'            => ''
 * 'limit'           => PHP_INT_MAX
 * 'relationship-id' => ''
 * 'field'           => ''
 * 'format'          => 'default'
 *
 *
 *
 * Class Civicrm_Ux_Shortcode_Activity_Listing
 */
class Civicrm_Ux_Shortcode_Activity_Listing extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 *
	 */
	const FIELD_ALIAS = [
		'contact_name' => [
			'return'    => 'target_contact_name',
			'api_param' => 'target_contact_id'
		],
	];

	/**
	 * @var
	 */
	private $sort_by;

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_activity_listing';
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
			'type'            => '',
			'limit'           => 0,
			'relationship-id' => '',
			'field'           => '',
			'format'          => 'default',
			'sort'            => 'activity_date_time DESC',
		], $atts, $tag );

		$cid = [ CRM_Core_Session::singleton()->getLoggedInContactID() ];
		if ( ! empty( $mod_atts['relationship-id'] ) ) {
			// get related contact ids
			try {
				$result = civicrm_api3( 'Relationship', 'get', [
					'sequential'           => 1,
					'relationship_type_id' => $mod_atts['relationship-id'],
					'contact_id_a'         => "user_contact_id",
					'contact_id_b'         => "user_contact_id",
					'options'              => [
						'or'    => [ [ "contact_id_a", "contact_id_b" ] ],
						'limit' => PHP_INT_MAX
					],
				] );
			} catch ( CiviCRM_API3_Exception $e ) {
				$result = [];
			}

			if ( $result['count'] > 0 ) {
				$oid = [];
				foreach ( $result['values'] as $relationship ) {
					$id_a = $relationship['contact_id_a'];
					$id_b = $relationship['contact_id_b'];
					if ( ! in_array( $id_a, $oid ) ) {
						$oid[] = $id_a;
					}

					if ( ! in_array( $id_b, $oid ) ) {
						$oid[] = $id_b;
					}
				}
				$cid = $oid;
			}
		}

		// parse fields
		$fields = explode( ',', $mod_atts['field'] );
		// map fields name and label
		$header = [];
		foreach ( $fields as $field ) {
			if ( strpos( $field, 'custom_' ) !== false ) {
				$fid = substr( $field, 7 );
				try {
					$result = civicrm_api3( 'CustomField', 'getsingle', [
						'id' => $fid,
					] );
				} catch ( CiviCRM_API3_Exception $e ) {
					$result = [];
				}
				if ( ! $result['is_error'] ) {
					$header[ $field ] = ucfirst( $result['label'] );
				}
			} else {
				$header[ $field ] = ucfirst( str_replace( '_', ' ', $field ) );
			}
		}

		// Solve alias
		foreach ( self::FIELD_ALIAS as $key => $value ) {
			foreach ( $fields as $index => $field ) {
				if ( $field == $key ) {
					$fields[ $index ] = $value['api_param'];
					break;
				}
			}
		}

		if ( strpos( $mod_atts['sort'], 'custom_' ) !== false ) {
			$this->sort_by    = $mod_atts['sort'];
			$mod_atts['sort'] = '';
		}

		// get activity information
		$params = [
			'sequential' => 1,
			'contact_id' => [ 'IN' => $cid ],
			'return'     => array_merge( [ "target_contact_id", "subject" ], $fields ),
			'options'    => [ 'limit' => $mod_atts['limit'], 'sort' => $mod_atts['sort'] ],
		];
		if ( ! empty( $mod_atts['type'] ) ) {
			$types                      = explode( ',', $mod_atts['type'] );
			$params['activity_type_id'] = [ 'IN' => $types ];
		}
		try {
			$result = civicrm_api3( 'Activity', 'get', $params );
		} catch ( CiviCRM_API3_Exception $e ) {
			$result = [];
		}

		if ( $result['count'] <= 0 ) {
			return 'No activity found.';
		}
		if ( isset( $this->sort_by ) ) {
			usort( $result['values'], [ $this, 'sort' ] );
		}

		// render table
		return $this->render( $result['values'], $header, $mod_atts['format'] );
	}

	/**
	 * Take data in and output the html
	 *
	 * @param array $info
	 * @param array $header
	 * @param string $format Set to table for a tabular layout
	 *
	 * @return string
	 */
	function render( $info, $header, $format = 'default' ) {
		$html = '';
		if ( $format == 'table' ) {
			$header_html = '';
			foreach ( $header as $key => $value ) {
				$header_html .= '<th>' . $value . '</th>';
			}
			$html = '<thead><tr>' . $header_html . '</tr></thead>';
			foreach ( $info as $data ) {
				$title = $data['subject'];
				$org   = end( $data['target_contact_name'] );
				$html  .= $this->get_table_row( $title, $org, $data, $header );
			}

			return '<table>' . $html . '</table>';
		} else {
			foreach ( $info as $data ) {
				$title = $data['subject'];
				$org   = end( $data['target_contact_name'] );
				$html  .= $this->get_item_html( $title, $org, $data, $header );
			}

			return '<div class="civicrm-activities-wrap">' . $html . '</div>';
		}
	}

	/**
	 * @param string $title
	 * @param string $org
	 * @param array $info
	 * @param array $header
	 *
	 * @return string
	 */
	private function get_item_html( $title, $org, $info, $header ) {
		$html = '';

		$fields_html = '';
		foreach ( $header as $key => $value ) {
			if ( array_key_exists( $key, self::FIELD_ALIAS ) ) {
				$key = self::FIELD_ALIAS[ $key ]['return'];
			}
			$data = array_key_exists( $key, $info ) ? $info[ $key ] : '';
			if ( is_array( $data ) ) {
				$data = implode( ', ', $data );
			}
			if ( ! empty( $data ) || $data == '0' ) {
				$fields_html .= '<label class="civicrm-activities-info-label">' . $value . ': <span>' . htmlentities($data) . '</span></label>';
			}
		}

		$html .= '<div class="civicrm-activities-item">' .
		         '<div class="civicrm-activities-header">' .
		         '<h2>' . htmlentities($org) . ': ' . htmlentities($title) . '</h2>' .
		         '</div>' .
		         '<div class="civicrm-activities-information">' .
		         $fields_html .
		         '</div>' .
		         '</div>';

		return $html;
	}

	/**
	 * @param string $title
	 * @param string $org
	 * @param array $info
	 * @param array $header
	 *
	 * @return string
	 */
	private function get_table_row( $title, $org, $info, $header ) {
		$html = '';
		foreach ( $header as $key => $value ) {
			if ( array_key_exists( $key, self::FIELD_ALIAS ) ) {
				$key = self::FIELD_ALIAS[ $key ]['return'];
			}
			$value = array_key_exists( $key, $info ) ? $info[ $key ] : '';
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}
			$html .= '<td>' . htmlentities($value) . '</td>';
		}

		return '<tr>' . $html . '</tr>';
	}


	/**
	 * Sorting the result with given order in the class field
	 *
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	private function sort( $a, $b ) {
		$sort_by = explode( ' ', $this->sort_by );
		if ( $sort_by[1] == 'ASC' ) {
			return strcmp( $a[ $sort_by[0] ], $b[ $sort_by[0] ] );
		} else {
			return strcmp( $b[ $sort_by[0] ], $a[ $sort_by[0] ] );
		}
	}
}