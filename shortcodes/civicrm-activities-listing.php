<?php

/**
 * Shortcode 'civicrm-activities-listing'
 * Accept parameters:
 * 'type'            => ''
 * 'limit'           => PHP_INT_MAX
 * 'relationship-id' => ''
 * 'field'           => ''
 * 'format'          => 'default'
 *
 *
 *
 * Class Civicrm_Ux_Shortcode_Activities_Listing
 */
class Civicrm_Ux_Shortcode_Activities_Listing implements iCivicrm_Ux_Shortcode {
	/**
	 * @var
	 */
	private $manager;

	/**
	 * @param \Civicrm_Ux_Shortcode_Manager $manager
	 *
	 * @return mixed
	 */
	public function init_setup( Civicrm_Ux_Shortcode_Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'civicrm-activities-listing';
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
			'limit'           => PHP_INT_MAX,
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

			if ( $result['count'] <= 0 ) {
				return 'No contact found with the given relationship id.';
			}

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
					$header[ $field ] = $result['label'];
				}
			} else {
				$header[ $field ] = str_replace( '_', ' ', $field );
			}
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
			$header_html = '<th>Contact Name</th><th>Subject</th>';
			foreach ( $header as $key => $value ) {
				$header_html .= '<th>' . $value . '</th>';
			}
			$html = '<thead><tr>' . $header_html . '</tr></thead>';
			foreach ( $info as $data ) {
				$title = $data['subject'];
				$org   = array_pop( $data['target_contact_name'] );
				$html  .= $this->get_table_row( $title, $org, $data, $header );
			}

			return '<table>' . $html . '</table>';
		} else {
			foreach ( $info as $data ) {
				$title = $data['subject'];
				$org   = array_pop( $data['target_contact_name'] );
				$html  .= $this->get_item_html( $title, $org, $data, $header );
			}

			return '<div class="civicrm-activicties-wrap">' . $html . '</div>';
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
			if ( array_key_exists( $key, $info ) && ( ! empty( $info[ $key ] || $info[ $key ] == '0' ) ) ) {
				$fields_html .= '<label class="civicrm-activicties-info-label">' . $value . ': <span>' . $info[ $key ] . '</span></label>';
			}
		}

		$html .= '<div class="civicrm-activicties-item">' .
		         '<div class="civicrm-activicties-header">' .
		         '<h2>' . $org . ': ' . $title . '</h2>' .
		         '</div>' .
		         '<div class="civicrm-activicties-information">' .
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
		$html = '<td>' . $org . '</td>' .
		        '<td>' . $title . '</td>';
		foreach ( $header as $key => $value ) {
			$html .= '<td>' . ( array_key_exists( $key, $info ) ? $info[ $key ] : '' ) . '</td>';
		}

		return '<tr>' . $html . '</tr>';
	}
}