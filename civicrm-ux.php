<?php

/**
 * @link              https://github.com/agileware/wp-civicrm-ux
 * @since             1.0.0
 * @package           Civicrm_Ux
 *
 * @wordpress-plugin
 * Plugin Name:       WP CiviCRM UX
 * Plugin URI:        https://github.com/agileware/wp-civicrm-ux
 * Description:       A better user experience for integrating WordPress and CiviCRM
 * Version:           1.11.2
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Agileware
 * Author URI:        https://agileware.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       civicrm-ux
 * Domain Path:       /languages
 * GitHub Plugin URI: agileware/wp-civicrm-ux
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// check CiviCRM activated
add_action( 'admin_init', function() {
    if ( ! is_plugin_active( 'civicrm/civicrm.php' ) && function_exists('deactivate_plugins') ) {
        add_action( 'admin_notices', 'agileware_caldera_forms_magic_tags_child_plugin_notice' );
    }
});

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-civicrm-ux-activator.php
 */
function activate_civicrm_ux() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-civicrm-ux-activator.php';
	Civicrm_Ux_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-civicrm-ux-deactivator.php
 */
function deactivate_civicrm_ux() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-civicrm-ux-deactivator.php';
	Civicrm_Ux_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_civicrm_ux' );
register_deactivation_hook( __FILE__, 'deactivate_civicrm_ux' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-civicrm-ux.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_civicrm_ux() {

	$plugin = Civicrm_Ux::getInstance( __FILE__ );
	$plugin->run();

}


/*
   AJAX handler to retreive all CiviCRM events and their properties
   with specified start date and event types
*/

function get_events_all() {
	$types = explode(',', filter_var($_REQUEST['type'], FILTER_SANITIZE_STRING));
	$start_date = preg_replace("([^0-9-])", "", $_REQUEST['start_date']);
	$force_login = rest_sanitize_boolean($_REQUEST['force_login']);
	$extra_fields = $_REQUEST['extra_fields'] != '' ? explode(',', filter_var($_REQUEST['extra_fields'], FILTER_SANITIZE_STRING)) : array();
	$colors = $_REQUEST['colors'];
	$upload = filter_var($_REQUEST['upload'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);

	foreach ($colors as $k => $v) {
		$colors[$k] = sanitize_hex_color_no_hash($v);
	}
	

	$res = array('success' => true);

	try {
		$events = array();
		if ($_REQUEST['image_id_field'] != "") {
			$image_id_field = $_REQUEST['image_id_field'];
			$image_src_field = $_REQUEST['image_src_field'];

			$events = \Civi\Api4\Event::get(FALSE)
                ->addSelect('id', 'title', 'summary', 'description', 'event_type_id:label', 'start_date', 'end_date', $image_src_field, 'address.street_address', 'address.street_number', 'address.street_number_suffix', 'address.street_name', 'address.street_type', 'address.country_id:label', 'is_online_registration', ...$extra_fields)
                ->addJoin('File AS file', 'LEFT', ['file.id', '=', $image_id_field])
                ->addJoin('LocBlock AS loc_block', 'INNER', ['loc_block_id', '=', 'loc_block_id.id'])
                ->addJoin('Address AS address', 'LEFT', ['loc_block.address_id', '=', 'address.id'])
                ->addWhere('event_type_id:label', 'IN', $types)
                ->addWhere('start_date', '>', $start_date)
				->execute();

			$res['result'] = array();

			


			foreach ($events as $event) {
				//$url = get_site_url() . '/civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Finfo&reset=1&id=' . $event['id']; 
				$url = CRM_Utils_System::url('civicrm/event/register', ['id' => $event['id'], 'reset' => 1]);

				if (!is_user_logged_in() and $force_login) {
					$url = get_site_url() . '/wp-login.php?redirect_to=' . $url;
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
						'html_render' => generate_event_html($event, $upload, $colors, $image_src_field, $url),
						'summary' => $event['summary'],
						'description' => $event['description'],
						'event_type' => $event['event_type_id:label'],
						'file.uri' => $event[$image_src_field],
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
			$events = \Civi\Api4\Event::get(FALSE)
                ->addSelect('id', 'title', 'summary', 'description', 'event_type_id:label', 'start_date', 'end_date', 'address.street_address', 'address.street_number', 'address.street_number_suffix', 'address.street_name', 'address.street_type', 'address.country_id:label', 'is_online_registration', ...$extra_fields)
                ->addJoin('LocBlock AS loc_block', 'INNER', ['loc_block_id', '=', 'loc_block_id.id'])
                ->addJoin('Address AS address', 'LEFT', ['loc_block.address_id', '=', 'address.id'])
                ->addWhere('event_type_id:label', 'IN', $types)
                ->addWhere('start_date', '>', $start_date)
				->execute();

			$res['result'] = array();


			foreach ($events as $event) {
				$url = CRM_Utils_System::url('civicrm/event/register', ['id' => $event['id'], 'reset' => 1]);

				if (!is_user_logged_in() and $force_login) {
					$url = get_site_url() . '/wp-login.php?redirect_to=' . $url;
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
						'html_render' => generate_event_html($event, $upload, $colors, $image_src_field, $url),
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
						'extra_fields' => $array()
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
	wp_die();

}

function formatDay($date) {
	return $date->format('l') . ', ' . $date->format('j') . ' ' . $date->format('F') . ' ' . $date->format('Y');
}

function formatAMPM($date) {
	return $date->format('g') . ':' . $date->format('i') . ' ' . $date->format('A');
}

function sameDay($d1, $d2) {
	return ($d1->format('j') == $d2->format('j')) &&  ($d1->format('f') == $d2->format('f')) && ($d1->format('Y') == $d2->format('Y'));
}

add_action( 'wp_ajax_get_events_all', 'get_events_all' );
add_action( 'wp_ajax_nopriv_get_events_all', 'get_events_all' );

function generate_event_html($event, $upload, $colors, $image_src_field, $url) {
	$date_start = date_create($event['start_date']);
	$date_end = date_create($event['end_date']);

	$event_start_day = formatDay($date_start);
	$event_end_day = formatDay($date_end);
	
	$event_start_hour = formatAMPM($date_start);
	$event_end_hour = formatAMPM($date_end);

	$event_time = '&nbsp;&nbsp;' . $event_start_day . '</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-clock-o"></i>&nbsp;&nbsp;<span id="event-time-text">' . $event_start_hour . ' to ' . $event_end_hour;

	if (!sameDay($date_start, $date_end)) {
		$event_time = '&nbsp;&nbsp;' . $event_start_day . '&nbsp;&nbsp;' . $event_start_hour . " to " . $event_end_day . '&nbsp;&nbsp;' . $event_end_hour;
	}

	$event_location = $event['address.street_address'] ? $event['address.street_address'] . ', ' : '';
	$event_location .= $event['address.country_id:label'] ? $event['address.country_id:label'] : '';

	$template = '<div class="civicrm-ux-event-listing">';
	$template .= $event[$image_src_field] ? '<div class="civicrm-ux-event-listing-image"><img src="' . $upload . '/' . $event[$image_src_field] . '"></div>' : '';
		
	$template .= '<div class="civicrm-ux-event-listing-type" style="background-color: ' . (count($colors) > 0 ? '#' . $colors[$event['event_type_id:label']] : '#333333') . ';">' . $event['event_type_id:label'] . '</div>
		<div class="civicrm-ux-event-listing-name">' . $event['title'] . '</div>
		<div class="civicrm-ux-event-listing-date"><i class="fa fa-calendar-o"></i><span id="event-time-text">' . $event_time . '</span></div>
		<div class="civicrm-ux-event-listing-location"><i class="fa fa-map-marker"></i>&nbsp;&nbsp;<span id="event-time-text">' . $event_location . '</span></div>';

	$template .= $event['is_online_registration'] ? '<div class="civicrm-ux-event-listing-register" onclick="window.location.href=\'' . $url . '\'">Click here to register</div>'  : '';
	$template .= '<div class="civicrm-ux-event-listing-desc">';
	$template .= $event['description'] ? $event['description'] . '</div>' : 'No event description provided</div>
	<hr>
	</div>';

	return $template;
}





run_civicrm_ux();
