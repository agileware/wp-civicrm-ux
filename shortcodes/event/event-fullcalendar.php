<?php

use Civi\Api4\Event;

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
                $extra_fields_arr = array_map([ 'Civicrm_Ux_Validators', 'validateAPIFieldName' ], explode(",", $atts['extra_fields']));
            }
			if (isset($atts['image_src_field'])) {
				$atts['image_src_field'] = Civicrm_Ux_Validators::validateAPIFieldName($atts['image_src_field']);
			}
			if (isset($atts['force_login'])) {
				$atts['force_login'] = filter_var($atts['force_login'], FILTER_VALIDATE_BOOLEAN);
			}
			if (isset($atts['redirect_after_login'])) {
				$atts['redirect_after_login'] = sanitize_text_field($atts['redirect_after_login']);
			}

		}

		// Shortcode parameters defaults
		$wporg_atts = shortcode_atts(
			array(
                'types' => NULL,
                'colors' => NULL,
				'force_login' => FALSE,
				'start' => date('Y-m-d', strtotime('-1 year')),
				'image_src_field' => 'file.uri',
				'extra_fields' => join(",", $extra_fields_arr)
			), $atts, $tag
		);

		$redirect_after_login = isset($atts['redirect_after_login']) ? $atts['redirect_after_login'] : '';

		$colors = [ 'default' => static::getDefaultColor() ];

        if(!empty($wporg_atts['types']) && !empty($wporg_atts['colors'])) {
            $types_arr = explode(',', $wporg_atts['types']);
            $limit = min(count($types_arr), count($colors_arr));
            for ($i = 0; $i < $limit; $i++) {
                $colors[$types_arr[$i]] = Civicrm_Ux_Validators::validateCssColor($colors_arr[$i]);
            }
        }

        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css', [] );
        wp_enqueue_style( 'fullcalendar-styles', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css', [] );
		wp_enqueue_style( 'ux-fullcalendar-styles', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/css/event-fullcalendar.css', [] );

		wp_enqueue_script( 'fullcalendar-base', 'https://cdn.jsdelivr.net/combine/npm/fullcalendar@5.11.5/main.js', [] );
		wp_enqueue_script( 'popper', 'https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js', [] );
		wp_enqueue_script( 'tippy', 'https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.js', [ 'popper' ] );
		wp_enqueue_script( 'ux-fullcalendar', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/js/event-fullcalendar.js', [ 'fullcalendar-base', 'popper', 'tippy', 'wp-api-request' ] );

        $ux_fullcalendar = [
            'ajax_url' => get_rest_url(),
            'redirect_after_login' => $redirect_after_login,
        ];

        if(!empty($colors)) {
            $ux_fullcalendar['colors'] = $colors;
        }

        $ux_fullcalendar = $ux_fullcalendar + array_filter($wporg_atts);

        if(!empty($ux_fullcalendar['types'])) {
            $ux_fullcalendar['filterTypes'] = explode(',', $ux_fullcalendar['types']);
        } else {
            $options = Event::getFields(FALSE)
                ->setLoadOptions([ 'name', 'label' ])
                ->addWhere('name', '=', 'event_type_id')
                ->addSelect('options')
                ->execute()
                ->first()['options'];

            $ux_fullcalendar['filterTypes'] = array_map(
                fn($_type) => $_type['label'],
            $options ?: []
            );
        }

        wp_localize_script( 'ux-fullcalendar', 'uxFullcalendar', $ux_fullcalendar );

		return '<div id="civicrm-event-fullcalendar" class="fullcalendar-container"></div>
		<div class="civicrm-ux-event-popup-container">
		<div class="civicrm-ux-event-popup">
			<button onclick="hideEventsCalendarPopup()" id="civicrm-ux-event-popup-close">&times;</button>
			<div id="civicrm-ux-event-popup-content"></div>
		</div></div>';
	}

    public static function getDefaultColor() {
        return apply_filters( 'ux_event_fullcalendar/default_color', 'transparent' );
    }

    public static function getDefaultForceLogin() {
        apply_filters( 'ux_event_fullcalendar/force_login', false );
    }
}
