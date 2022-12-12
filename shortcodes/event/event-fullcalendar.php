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
		wp_enqueue_script( 'ical', 'https://cdnjs.cloudflare.com/ajax/libs/ical.js/1.4.0/ical.js', [] );
		wp_enqueue_script( 'fullcalendar-base', 'https://cdn.jsdelivr.net/combine/npm/fullcalendar@5.9.0/main.js', [] );
		wp_enqueue_script( 'popper', 'https://unpkg.com/popper.js/dist/umd/popper.min.js', [] );
		wp_enqueue_script( 'tooltip', 'https://unpkg.com/tooltip.js/dist/umd/tooltip.min.js', [] );
		wp_enqueue_script( 'fullcalendar-ical', 'https://cdn.jsdelivr.net/npm/@fullcalendar/icalendar@5.9.0/main.global.js', [ 'fullcalendar-base' ] );
		wp_enqueue_style( 'fullcalendar-styles', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.css', [] );
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [] );
		wp_enqueue_script( 'ux-fullcalendar', plugin_dir_url( dirname( __FILE__ ) . '/../../..' ) . 'public/js/event-fullcalendar.js', [ 'fullcalendar-ical' ], '1.7.0' );
		wp_localize_script( 'ux-fullcalendar', 'my_ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );



		

		return '<div id="civicrm-event-fullcalendar" class="fullcalendar-container"></div>
		<div id="civicrm-ux-event-popup">
			<button id="civicrm-ux-event-popup-close">&times;</button>
			<img id="civicrm-ux-event-popup-img" src="">
			<div id="civicrm-ux-event-popup-eventtype"></div>
			<h2 id="civicrm-ux-event-popup-header"></h2>
			<h4 id="civicrm-ux-event-popup-summary"></h4>
			<div id="civicrm-ux-event-popup-time">
				<i class="fa fa-calendar-o"></i>
				<span id="civicrm-ux-event-popup-time-day"></span>
				<i class="fa fa-clock-o"></i>
				<span id="civicrm-ux-event-popup-time-hours"><span>
			</div>
			<div id="civicrm-ex-event-popup-location">
				<i class="fa fa-map-marker-alt"></i>
				<span></span>
			</div>
			<p id="civicrm-ux-event-popup-desc"></p>
		</div>';
	}
}
