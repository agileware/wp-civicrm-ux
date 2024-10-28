<?php

use Civi\Api4\Event;

class Civicrm_Ux_REST_JSON_All_Events extends Abstract_Civicrm_Ux_REST {

	/**
	 * @return string
	 */
	public function get_route() {
		return 'civicrm_ux';
	}

	/**
	 * @return string
	 */
	public function get_endpoint() {
		return 'get_events_all';
	}

	/**
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * @param \WP_REST_Request $data
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function rest_api_callback( $data ) {
		header( 'Content-Type: application/json' );
		$this->get_events_all();
		exit();
	}

	/**
	 * AJAX handler to retrieve all CiviCRM events and their properties
	 * with specified start date and event types
	 */
	protected function get_events_all() {
		$types = array();
		$start_date = preg_replace("([^0-9-])", "", $_REQUEST['start_date']);
		$force_login = rest_sanitize_boolean($_REQUEST['force_login'] ?? apply_filters( 'ux_event_fullcalendar/force_login', false ) );
		$redirect_after_login = esc_url($_REQUEST['redirect_after_login']);
		$extra_fields = !empty($_REQUEST['extra_fields']) ? explode(',', filter_var($_REQUEST['extra_fields'], FILTER_SANITIZE_STRING)) : [];
		$colors = $_REQUEST['colors'] ?? [];
		$upload = filter_var($_REQUEST['upload'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);

        $colors['default'] = apply_filters( 'ux_event_fullcalendar/default_color', 'transparent' );

		if (isset($_REQUEST['type'])) {
			$types_tmp = explode(",", $_REQUEST['type']);
			for ($i = 0; $i < count($types_tmp); $i++) {
				array_push($types, preg_replace('/[^a-zA-Z0-9 ]/', '', $types_tmp[$i]));
			}

		}

		foreach ($colors as $k => $v) {
			$colors[$k] = sanitize_hex_color_no_hash($v);
			if (!ctype_alnum($k)) {
				unset($colors[$k]);
			}
		}


		$res = array('success' => true);

		try {
			$events = array();

            $eventQuery =  Event::get(FALSE)
                ->addSelect('id', 'title', 'summary', 'description', 'event_type_id:label', 'start_date', 'end_date', 'address.street_address', 'address.street_number', 'address.street_number_suffix', 'address.street_name', 'address.street_type', 'address.country_id:label', 'is_online_registration', ...$extra_fields)
                ->addJoin('LocBlock AS loc_block', 'LEFT', ['loc_block_id', '=', 'loc_block_id.id'])
                ->addJoin('Address AS address', 'LEFT', ['loc_block.address_id', '=', 'address.id'])
                ->addWhere('start_date', '>=', $start_date)
                ->addWhere('is_public', '=', TRUE)
                ->addWhere('is_active', '=', TRUE)
                ->addOrderBy('start_date', 'ASC');

            if(!empty($types)) {
                $eventQuery->addWhere('event_type_id:name', 'IN', $types);
            }

			if (!empty($_REQUEST['image_id_field'])) {
				$image_id_field = $_REQUEST['image_id_field'];
				$image_src_field = $_REQUEST['image_src_field'];

                $eventQuery
                    ->addJoin('File AS file', 'LEFT', ['file.id', '=', $image_id_field])
                    ->addSelect('file.id', $image_src_field);

				$events = $eventQuery-execute();

				$res['result'] = array();

				$tz = wp_timezone();

				foreach ($events as $event) {
					if ( !empty($redirect_after_login) ) {
						// If we have specified a custom redirection after login, apply that
						$params = array(
							'id' => $event['id'],
							'reset' => 1
						);
						$url = add_query_arg($params, $redirect_after_login);
					} else {
						// Otherwise redirect to the standard civicrm event registration page
						$url = CRM_Utils_System::url('civicrm/event/register', ['id' => $event['id'], 'reset' => 1]);
					}

					if (!is_user_logged_in() and $force_login) {
						$url = get_site_url() . '/wp-login.php?redirect_to=' . urlencode($url);
					}

					if (str_ends_with($upload, '/civicrm/custom')) {
						$fileHash = CRM_Core_BAO_File::generateFileHash($event['id'], $event['file.id']);
						$image_url = CRM_Utils_System::url('civicrm/file/imagefile',"reset=1&id={$event['file.id']}&eid={$event['id']}&fcs={$fileHash}");
					} else {
						$image_url = $upload . '/' . $event[$image_src_field];
					}

					$event_obj = array(
						'id' => $event['id'],
						'title' => $event['title'],
						'start' => (new DateTimeImmutable($event['start_date'], $tz))->setTimezone($tz)->format(DateTime::ISO8601),
						'end' => (new DateTimeImmutable($event['end_date'], $tz))->setTimezone($tz)->format(DateTime::ISO8601),
						'display' => 'auto',
						'startStr' => $event['start_date'],
						'endStr' => $event['end_date'],
						'url' => $url,
						'extendedProps' => array(
							'html_render' => $this->generate_event_html($event, $upload, $colors, $image_src_field, $url),
							'summary' => $event['summary'],
							'description' => $event['description'],
							'event_type' => $event['event_type_id:label'],
							'file.uri' => $event[$image_src_field],
							'image_url' => $image_url,
							'street_address' => $event['address.street_address'],
							'street_number' => $event['address.street_number'],
							'street_number_suffix' => $event['address.street_number_suffix'],
							'street_name' => $event['address.street_name'],
							'street_type' => $event['address.street_type'],
							'country' => $event['address.country_id:label'],
							'is_online_registration' => $event['is_online_registration'],
							'extra_fields' => array()
						)
					);

					for ($i = 0; $i < count($extra_fields); $i++) {
						$event_obj['extra_fields'][$extra_fields[$i]] = $event[$extra_fields[$i]];
					}

					$event_obj = apply_filters( 'wp_civi_ux_event_inject_content', $event_obj );

					array_push($res['result'], $event_obj);
				}
			} else {

				$image_src_field = $_REQUEST['image_src_field'];

				$events = $eventQuery->execute();

				$res['result'] = array();

				foreach ($events as $event) {
					if ( !empty($redirect_after_login) ) {
						// If we have specified a custom redirection after login, apply that
						$params = array(
							'id' => $event['id'],
							'reset' => 1
						);
						$url = add_query_arg($params, $redirect_after_login);
					} else {
						// Otherwise redirect to the standard civicrm event registration page
						$url = CRM_Utils_System::url('civicrm/event/register', ['id' => $event['id'], 'reset' => 1]);
					}

					if (!is_user_logged_in() and $force_login) {
						$url = get_site_url() . '/wp-login.php?redirect_to=' . urlencode($url);
					}

					$event_obj = array(
						'id' => $event['id'],
						'title' => $event['title'],
						'start' => date(DATE_ISO8601, strtotime($event['start_date'])),
						'end' => date(DATE_ISO8601, strtotime($event['end_date'])),
						'display' => 'auto',
						'startStr' => $event['start_date'],
						'endStr' => $event['end_date'],
						'url' => $url,
						'extendedProps' => array(
							'html_render' => $this->generate_event_html($event, $upload, $colors, $image_src_field, $url),
							'summary' => $event['summary'],
							'description' => $event['description'],
							'event_type' => $event['event_type_id:label'],
							'street_address' => $event['address.street_address'],
							'street_number' => $event['address.street_number'],
							'street_number_suffix' => $event['address.street_number_suffix'],
							'street_name' => $event['address.street_name'],
							'street_type' => $event['address.street_type'],
							'country' => $event['address.country_id:label'],
							'is_online_registration' => $event['is_online_registration'],
							'extra_fields' => array()
						)
					);

					for ($i = 0; $i < count($extra_fields); $i++) {
						$event_obj['extra_fields'][$extra_fields[$i]] = $event[$extra_fields[$i]];
					}

					$event_obj = apply_filters( 'wp_civi_ux_event_inject_content', $event_obj );

					array_push($res['result'], $event_obj);
				}

			}
		} catch (CiviCRM_API4_Exception $e) {
			$res['err'] = $e;
		}

		echo json_encode($res);
	}

	protected function generate_event_html($event, $upload, $colors, $image_src_field, $url) {
		$date_start = date_create($event['start_date']);
		$date_end = date_create($event['end_date']);

		$event_start_day = self::formatDay( $date_start );
		$event_end_day = self::formatDay( $date_end );

		$event_start_hour = self::formatAMPM( $date_start );
		$event_end_hour = self::formatAMPM( $date_end );

		$event_time = $event_start_day . '</span><i class="fa fa-clock-o"></i><span class="event-time-text">' . $event_start_hour . ' to ' . $event_end_hour;

		if (! self::sameDay( $date_start, $date_end ) ) {
			$event_time = $event_start_day . ' ' . $event_start_hour . " to " . $event_end_day . ' ' . $event_end_hour;
		}

		$event_location = $event['address.street_address'] ? $event['address.street_address'] . ', ' : '';
		$event_location .= $event['address.country_id:label'] ? $event['address.country_id:label'] : '';

		if (str_ends_with($upload, '/civicrm/custom') && !empty($event['file.id'])) {
			$fileHash = CRM_Core_BAO_File::generateFileHash($event['id'], $event['file.id']);
			$image_url = CRM_Utils_System::url('civicrm/file/imagefile',"reset=1&id={$event['file.id']}&eid={$event['id']}&fcs={$fileHash}", true, "", false, true, false);
		} elseif ( ! empty($event[$image_src_field]) ) {
            $image_url = $upload . '/' . $event[$image_src_field];
		} else {
            $image_url = '';
        }

        $template_args = compact(
            'event',
            'image_src_field',
            'image_url',
            'colors',
            'event_time',
            'event_location',
            'url'
        );

        ob_start();

        civicrm_ux_load_template_part('event-fullcalendar', 'event', $template_args);

		return ob_get_clean();
	}

	protected static function formatDay( $date ) {
		return $date->format( 'l' ) . ', ' . $date->format( 'j' ) . ' ' . $date->format( 'F' ) . ' ' . $date->format( 'Y' );
	}

	protected static function formatAMPM( $date ) {
		return $date->format( 'g' ) . ':' . $date->format( 'i' ) . ' ' . $date->format( 'A' );
	}

	protected static function sameDay( $d1, $d2 ) {
		return ( $d1->format( 'j' ) == $d2->format( 'j' ) ) && ( $d1->format( 'f' ) == $d2->format( 'f' ) ) && ( $d1->format( 'Y' ) == $d2->format( 'Y' ) );
	}

}
