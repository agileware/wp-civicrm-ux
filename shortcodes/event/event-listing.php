<?php


class Civicrm_Ux_Shortcode_Event_Listing extends Abstract_Civicrm_Ux_Shortcode{

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_listing';
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
			'options'    => [ 'sort' => "start_date ASC", 'limit' => PHP_INT_MAX ],
		];

		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		// override default attributes with user attributes
		$mod_atts = shortcode_atts( [
			'type' => '',
		], $atts, $tag );

		if ( ! empty( $mod_atts['type'] ) ) {
			$types                       = explode( ',', $mod_atts['type'] );
			$civi_param['event_type_id'] = [ 'IN' => $types ];
		}

		try {
			$result = civicrm_api3( 'Event', 'get', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			return "error";
		}

		$current_time_group = '';
		$output             = '<div class="civicrm-event-listing-wrap">';
		foreach ( $result['values'] as $event ) {
			$date = $this->get_date_object( $event['event_start_date'] )->format( 'F, Y' );

			if ( empty( $current_time_group ) || $current_time_group !== $date ) {
				$current_time_group = $date;
				$output             .= '<h2>' . $current_time_group . '</h2>';
			}
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

			// Check if end date is the same day as start date
			if ( $end_date->format( 'j F Y' ) == $start_date->format( 'j F Y' ) ) {
				$end_date_text = ' to ' . $end_date->format( 'g:i a' );
			} else {
				$end_date_text = ' to ' . $end_date->format( $date_format );
			}
		}

		$output = '<h3 class="civicrm-event-item-header">' . $this->get_event_info_link_html( $event['id'], $event['title'] ) . '</h3>' .
		          '<p class="civicrm-event-item-date"><b>' . $start_date->format( $date_format ) . $end_date_text . '</b> - ' . $this->get_event_register_link_html( $event['id'] ) . '</p>' .
		          '<p class="civicrm-event-item-summary">' . $event['summary'] . ' ' . $this->get_event_info_link_html( $event['id'] ) . '</p>';

		return "<div class='civicrm-event-listing-item'>$output</div>";
	}

	private function get_date_object( string $date ) {
		$format = 'Y-m-d H:i:s';

		return DateTime::createFromFormat( $format, $date );
	}

	private function get_event_register_link_html( string $event_id ) {
		$url = home_url( '/' ) . 'civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Fregister&reset=1&id=' . $event_id;

		return '<a target=_blank href="' . $url . '">Register now</a>';
	}

	private function get_event_info_link_html( string $event_id, string $text = 'More information' ) {
		$url = home_url( '/' ) . 'civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Finfo&reset=1&id=' . $event_id;

		return '<a target=_blank href="' . $url . '">' . $text . '</a>';
	}
}