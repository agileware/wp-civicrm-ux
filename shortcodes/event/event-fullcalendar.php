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
		global $wpdb;

		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		$types = array();
        $types_ = \Civi\Api4\Event::getFields(FALSE)
            ->setLoadOptions([
                'name',
                'label',
            ])
            ->addWhere('name', '=', 'event_type_id')
            ->addSelect('options')
            ->execute()[0]['options'];
        foreach ($types_ as $type) {
            array_push($types, $type['label']);
        }

		$wporg_atts = shortcode_atts(
			array(
				'types' => join(',', $types),
				'upload' => wp_upload_dir()['baseurl'] . '/civicrm/custom',
				'force_login' => true,
				'image_src_field' => 'file.uri',
				'extra_fields' => ''
			), $atts, $tag
		);

		$colors = array();

		if (isset($atts['colors'])) {
			$colors_arr = explode(',', $atts['colors']);
			$types_arr = explode(',', $wporg_atts['types']);
			for ($i = 0; $i < count($colors_arr); $i++) {
				if ($i >= count($types_arr)) {
					break;
				}
				$colors[$types_arr[$i]] = $colors_arr[$i];
			}
		}

		

		wp_enqueue_script( 'ical', 'https://cdnjs.cloudflare.com/ajax/libs/ical.js/1.4.0/ical.js', [] );
		wp_enqueue_script( 'fullcalendar-base', 'https://cdn.jsdelivr.net/combine/npm/fullcalendar@5.9.0/main.js', [] );
		wp_enqueue_script( 'popper', 'https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js', [] );
		wp_enqueue_script( 'tippy', 'https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.js', [] );
		wp_enqueue_script( 'fullcalendar-ical', 'https://cdn.jsdelivr.net/npm/@fullcalendar/icalendar@5.9.0/main.global.js', [ 'fullcalendar-base' ] );
		wp_enqueue_style( 'fullcalendar-styles', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.css', [] );
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [] );
		wp_enqueue_script( 'ux-fullcalendar', plugin_dir_url( dirname( __FILE__ ) . '/../../..' ) . 'public/js/event-fullcalendar.js', [ 'fullcalendar-ical' ], '1.7.0' );
		wp_localize_script( 'ux-fullcalendar', 'wp_site_obj',
            array( 'ajax_url' => admin_url( 'admin-ajax.php'), 
            	'upload' => $wporg_atts['upload'],
            	'types' => $wporg_atts['types'], 
            	'colors' => $colors,
				'start' => isset($wporg_atts['start']) ? $wporg_atts['start'] : date('Y-m-d', strtotime('-1 year')),
			    'image_id_field' => $atts['image_id_field'],
				'image_src_field' => $wporg_atts['image_src_field'],
				'force_login' => $wporg_atts['force_login'],
				'extra_fields' => $wporg_atts['extra_fields']));



		

		return '<div id="civicrm-event-fullcalendar" class="fullcalendar-container"></div>
		<div id="civicrm-ux-event-popup-container">
		<div id="civicrm-ux-event-popup">
			<button onclick="hidePopup()" id="civicrm-ux-event-popup-close">&times;</button>
			<div id="civicrm-ux-event-popup-content"></div>
		</div></div>';
	}
}
