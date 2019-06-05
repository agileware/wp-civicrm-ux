<?php

class Agileware_Civicrm_Utilities_Shortcode_Upcoming_Event implements iAgileware_Civicrm_Utilities_Shortcode {



	/**
	 * @param \Agileware_Civicrm_Utilities_Shortcode_Manager $manager
	 *
	 * @return mixed
	 */
	public function init_setup( Agileware_Civicrm_Utilities_Shortcode_Manager $manager ) {

	}

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'civicrm-upcoming-events';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = NULL, $tag = '' ) {
		$civi_param = [
			'sequential' => 1,
			'is_public'  => 1,
			'is_active'  => 1,
			'start_date' => [ '>' => "now" ],
			'options'    => [ 'sort' => "start_date ASC", 'limit' => 5 ],
		];

		if ( !empty($atts) && $atts['count'] && is_integer(atts['count']) ) {
			$civi_param['options']['limit'] = $atts['count'];
		}

		try {
			$result = civicrm_api3( 'Event', 'get', $civi_param );
		} catch ( CiviCRM_API3_Exception $e ) {
			return "error";
		}

		$output             = '<div class="civicrm-upcoming-event-wrap">';
		foreach ( $result['values'] as $event ) {
			$output .= $this->get_event_item_html( $event );
		}

		$output .= '</div>';

		return $output;
	}

	private function get_event_item_html( array $event ) {
		$start_date        = $this->get_date_object( $event['event_start_date'] );
		if ($start_date->format('h:i a') == '12:00 am') {
			$date_format = 'j F Y';
		} else{
			$date_format = 'j F Y g:i a';
		}

		$end_date_text = '';
		if ( ! empty( $event['event_end_date'] ) ) {
			$end_date = $this->get_date_object( $event['event_end_date'] );

			// Check if end date is the same day as start date
			if ($end_date->format('j F Y') == $start_date->format('j F Y')) {
				$end_date_text = ' to ' . $end_date->format('g:i a');
			} else {
				$end_date_text = ' to ' . $end_date->format( $date_format );
			}
		}

		$output = '<p><strong>' . $this->get_event_info_link_html( $event['id'], $event['title'] ) . '</strong></p>' .
		          '<p><b>' . $start_date->format( $date_format ) . $end_date_text . '</b></p>';

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