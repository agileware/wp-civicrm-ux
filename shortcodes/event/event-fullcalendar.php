<?php

class Civicrm_Ux_Shortcode_Event_FullCalendar extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_fullcalendar';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		wp_enqueue_script( 'ical', 'https://cdnjs.cloudflare.com/ajax/libs/ical.js/1.4.0/ical.min.js', [] );
		wp_enqueue_script( 'fullcalendar-base', 'https://cdn.jsdelivr.net/combine/npm/fullcalendar@5.9.0/main.js', [] );
		wp_enqueue_script( 'fullcalendar-ical', 'https://cdn.jsdelivr.net/npm/@fullcalendar/icalendar@5.9.0/main.global.js', [ 'fullcalendar-base' ] );
		wp_enqueue_style( 'fullcalendar-styles', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.css', [] );
		wp_enqueue_script( 'ux-fullcalendar', plugin_dir_url( dirname( __FILE__ ) . '/../../..' ) . 'public/js/event-fullcalendar.js', [ 'fullcalendar-ical' ], '1.7.0' );

		return '<div id="civicrm-event-fullcalendar" class="fullcalendar-container"></div>';
	}
}
