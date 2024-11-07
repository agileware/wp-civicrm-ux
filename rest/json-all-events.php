<?php

use Civi\Api4\Event;

use Civicrm_Ux_Shortcode_Event_FullCalendar as Shortcode;

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
        $upload = wp_get_upload_dir()['url'];
        $types = array();

		$start_date = preg_replace("([^0-9-])", "", $_REQUEST['start_date']);
        $force_login = rest_sanitize_boolean($_REQUEST['force_login'] ?? Shortcode::getDefaultForceLogin());
		$redirect_after_login = esc_url($_REQUEST['redirect_after_login']);
		$extra_fields = !empty( $_REQUEST['extra_fields'] ) ? explode( ',', $_REQUEST['extra_fields'] ) : [];
		$extra_fields = array_filter($extra_fields, fn($field) => Civicrm_Ux_Validators::validateAPIFieldName( $field, 'extra_fields' ));

        if(!empty($_REQUEST['colors']) && !is_array($_REQUEST['colors'])) {
            $_REQUEST['colors'] = explode(',', $_REQUEST['colors']);
        }
		$colors = array_map ( [ 'Civicrm_Ux_Validators', 'validateCssColor' ], $_REQUEST['colors'] ?? [] );

        $colors = array_filter($colors);
        $colors[ 'default' ] ??= Shortcode::getDefaultColor();

		if (isset($_REQUEST['type'])) {
			$types_tmp = explode(",", $_REQUEST['type']);
			for ($i = 0; $i < count($types_tmp); $i++) {
				array_push($types, preg_replace('/[^a-zA-Z0-9 ]/', '', $types_tmp[$i]));
			}
		}

        foreach($types as $idx => $type) {
            $colors[$type] ??= $colors[$idx] ?? Shortcode::getDefaultColor();
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

            if(!empty($_REQUEST['image_src_field'])) {
                $image_src_field = Civicrm_Ux_Validators::validateAPIFieldName($_REQUEST['image_src_field'], 'image_src_field');
                $eventQuery->addSelect($image_src_field);
            } else {
                $image_src_field = null;
            }

            $events = $eventQuery->execute();

            $res['result'] = array();

            $tz = wp_timezone();

            foreach ($events as $event) {
                if ( !empty($redirect_after_login) ) {
                    // If we have specified a custom redirection after login, apply that
                    $params = [ 'id' => $event['id'], 'reset' => 1, ];

                    $url = add_query_arg($params, $redirect_after_login);

                } else {
                    // Otherwise redirect to the standard civicrm event registration page
                    $url = CRM_Utils_System::url('civicrm/event/register', ['id' => $event['id'], 'reset' => 1]);
                }

                if (!is_user_logged_in() and $force_login) {
                    $url = get_site_url() . '/wp-login.php?redirect_to=' . urlencode($url);
                }

                $event += [
                    'url'   => $url,
                    'start' => new DateTimeImmutable($event['start_date'],  $tz),
                    'end'   => new DateTimeImmutable($event['end_date'], $tz),
                ];

                $event_obj = array(
                    'id'            => $event['id'],
                    'title'         => $event['title'],
                    'start'         => $event['start']->format( DateTime::ATOM ),
                    'end'           => $event[ 'end' ]->format( DateTime::ATOM ),

                    'display'       => 'auto',
                    'startStr'      => $event['start_date'],
                    'endStr'        => $event['end_date'],
                    'url'           => $url,
                    'extendedProps' => array(
                        'html_render'            => $this->generate_event_html($event, $upload, $colors, $image_src_field, $url),
                        'html_entry'             => $this->get_output_template(['event' => $event]),
                        'timeZone'               => $tz->getName(),
                        'summary'                => $event['summary'],
                        'description'            => $event['description'],
                        'event_type'             => $event['event_type_id:label'],
                        'street_address'         => $event['address.street_address'],
                        'street_number'          => $event['address.street_number'],
                        'street_number_suffix'   => $event['address.street_number_suffix'],
                        'street_name'            => $event['address.street_name'],
                        'street_type'            => $event['address.street_type'],
                        'country'                => $event['address.country_id:label'],
                        'is_online_registration' => $event['is_online_registration'],
                        'extra_fields'           => []
                    )
                );

                if(!empty($image_src_field) && !empty($event[$image_src_field])) {
                    $image_url = $upload . '/' . $event[$image_src_field];

                    $event_obj['extendedProps']['file.uri'] = $event[$image_src_field];
                    $event_obj['extendedProps']['image_url'] = $image_url;
                }

                foreach ($extra_fields as $field) {
                    $event_obj['extra_fields'][$field] = $event[$field];
                }

                $event_obj = apply_filters( 'wp_civi_ux_event_inject_content', $event_obj );

                $res['result'][] = $event_obj;
			}
		} catch (CRM_Core_Exception $e) {
            http_response_code(500);
			$res['err'] = $e->getMessage();
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

        if ( ! empty($event[$image_src_field]) ) {
            $image_url = trailingslashit( $upload ) . ltrim( $event[$image_src_field], '/' );
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

        return civicrm_ux_get_template_part('event-fullcalendar', 'event', $template_args);
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

    protected function get_output_template($args) {
        return civicrm_ux_get_template_part('event-fullcalendar', 'event-entry', $args);
    }
}
