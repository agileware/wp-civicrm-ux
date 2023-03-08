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
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

		$colors_arr = array();
		$extra_fields_arr = array();

		// Sanitize shortcode parameters
		if (count($atts) > 1) {
			if (isset($atts['types'])) {
				$types_tmp = explode(",", $atts['types']);
				for ($i = 0; $i < count($types_tmp); $i++) {
					$types_tmp[$i] = preg_replace('/[^a-zA-Z0-9 ]/', '', $types_tmp[$i]);
				}
				$atts['types'] = implode(",", $types_tmp);
			}
			if (isset($atts['colors'])) {
				$colors_tmp = explode(",", $atts['colors']);
				foreach ($colors_tmp as $color) {
					array_push($colors_arr, sanitize_hex_color_no_hash($color));
				}
			}
			if (isset($atts['extra_fields'])) {
				$extra_fields_tmp = explode(",", $atts['extra_fields']);
				foreach ($extra_fields_tmp as $field) {
					array_push($extra_fields_arr, preg_replace('/[^a-zA-Z0-9._]/', '', $field));
				}
			}
			if (isset($atts['upload'])) {
				$atts['upload'] = sanitize_text_field($atts['upload']);
			}
			if (isset($atts['image_id_field'])) {
				$atts['image_id_field'] = preg_replace('/[^a-zA-Z0-9._]/', '', $atts['image_id_field']);
			}
			if (isset($atts['image_src_field'])) {
				$atts['image_src_field'] = preg_replace('/[^a-zA-Z0-9._]/', '', $atts['image_src_field']);
			}
			if (isset($atts['force_login'])) {
				$atts['force_login'] = preg_replace('/[^0-1]/', '', $atts['force_login']);
			}

		}

		$types = array();
		$types_ = \Civi\Api4\Event::getFields(FALSE)
		                          ->setLoadOptions([
			                          'name',
			                          'label'
		                          ])
		                          ->addWhere('name', '=', 'event_type_id')
		                          ->addSelect('options')
		                          ->execute()[0]['options'];
		foreach ($types_ as $type) {
			array_push($types, $type['name']);
		}

		$wporg_atts = shortcode_atts(
			array(
				'types' => join(',', $types),
				'upload' => wp_upload_dir()['baseurl'] . '/civicrm/custom',
				'force_login' => true,
				'start' => date('Y-m-d', strtotime('-1 year')),
				'image_src_field' => 'file.uri',
				'extra_fields' => join(",", $extra_fields_arr)
			), $atts, $tag
		);

		$colors = array();

		if (isset($atts['colors'])) {
			$types_arr = explode(',', $wporg_atts['types']);
			for ($i = 0; $i < count($colors_arr); $i++) {
				if ($i >= count($types_arr)) {
					break;
				}
				$colors[$types_arr[$i]] = $colors_arr[$i];
			}
		} else {
			$types_arr = explode(',', $wporg_atts['types']);
			for ($i = 0; $i < count($types_arr); $i++) {
				$colors[$types_arr[$i]] = '333333';
			}
		}


		wp_enqueue_style( 'ux-fullcalendar-styles', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/css/event-fullcalendar.css', [] );
		wp_enqueue_style( 'fullcalendar-styles', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.9.0/main.min.css', [] );
		wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [] );

		wp_enqueue_script( 'fullcalendar-base', 'https://cdn.jsdelivr.net/combine/npm/fullcalendar@5.9.0/main.js', [] );
		wp_enqueue_script( 'popper', 'https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js', [] );
		wp_enqueue_script( 'tippy', 'https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.js', [] );
		wp_enqueue_script( 'ux-fullcalendar', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/js/event-fullcalendar.js', [] );


		wp_localize_script( 'ux-fullcalendar', 'wp_site_obj',
			array( 'ajax_url' => get_rest_url(),
			       'upload' => $wporg_atts['upload'],
			       'types' => $wporg_atts['types'],
			       'colors' => $colors,
			       'start' => $wporg_atts['start'],
			       'image_id_field' => $atts['image_id_field'],
			       'image_src_field' => $wporg_atts['image_src_field'],
			       'force_login' => $wporg_atts['force_login'],
			       'extra_fields' => $wporg_atts['extra_fields']));

		return '<div id="civicrm-event-fullcalendar" class="fullcalendar-container"></div>
		<div class="civicrm-ux-event-popup-container">
		<div class="civicrm-ux-event-popup">
			<button onclick="hidePopup()" id="civicrm-ux-event-popup-close">&times;</button>
			<div id="civicrm-ux-event-popup-content"></div>
		</div></div>';
	}
}
