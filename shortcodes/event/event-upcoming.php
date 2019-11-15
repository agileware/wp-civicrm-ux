<?php

class Civicrm_Ux_Shortcode_Event_Upcoming extends Abstract_Civicrm_Ux_Shortcode{

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_upcoming';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		$civi_param = [
			'sequential' => 1,
			'is_public'  => 1,
			'is_active'  => 1,
			'start_date' => [ '>' => "now" ],
			'options'    => [ 'sort' => "start_date ASC" ],
		];

		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'type'  => '',
			'count' => 5,
		], $atts, $tag );

		if ( ! empty( $mod_atts['type'] ) ) {
			$types                       = explode( ',', $mod_atts['type'] );
			$civi_param['event_type_id'] = [ 'IN' => $types ];
		}
		$civi_param['options']['limit'] = $mod_atts['count'];


		try {
			$result = civicrm_api3( 'Event', 'get', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			return "error";
		}

		$output = '<div class="civicrm-upcoming-event-wrap">';
		foreach ( $result['values'] as $event ) {
			$output .= $this->get_event_item_html( $event );
		}

		$output .= '</div>';

		return $output;
	}

	private function get_event_item_html( array $event ) {
		$start_date = $this->get_date_object( $event['event_start_date'] );
		if ( $start_date->format( 'h:i a' ) == '12:00 am' ) {
			$date_format = 'j F Y';
		} else {
			$date_format = 'j F Y g:i a';
		}

		$end_date_text = '';
		if ( ! empty( $event['event_end_date'] ) ) {
			$end_date = $this->get_date_object( $event['event_end_date'] );
			if ( $end_date->format( 'h:i a' ) == '12:00 am' ) {
				$end_date_format = 'j F Y';
			} else {
				$end_date_format = 'j F Y g:i a';
			}

			// Check if end date is the same day as start date
			if ( $end_date->format( 'j F Y' ) == $start_date->format( 'j F Y' ) ) {
				$end_date_format = str_replace( [ 'j F Y ', 'j F Y' ], '', $end_date_format );
				$end_date_text   = ' to ' . $end_date->format( $end_date_format );
			} else {
				$end_date_text = ' to ' . $end_date->format( $end_date_format );
			}

			if ( $this->manager->get_plugin()->helper->ends_with( $end_date_text, ' to ' ) ) {
				$end_date_text = substr( $end_date_text, 0, strlen( $end_date_text ) - 4 );
			}
		}

		$output = '<p class="civicrm-upcoming-event-title"><strong>' . $this->get_event_info_link_html( $event['id'], $event['title'] ) . '</strong></p>' .
		          '<p class="civicrm-upcoming-event-date">' . $start_date->format( $date_format ) . $end_date_text . '</p>';

		return "<div class='civicrm-upcoming-event-item'>$output</div>";
	}


	private function get_date_object( string $date ) {
		$format = 'Y-m-d H:i:s';

		return DateTime::createFromFormat( $format, $date );
	}

	private function get_event_info_link_html( string $event_id, string $text ) {
		$url = home_url( '/' ) . 'civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Finfo&reset=1&id=' . $event_id;

		return '<a target=_blank href="' . $url . '">' . $text . '</a>';
	}
}