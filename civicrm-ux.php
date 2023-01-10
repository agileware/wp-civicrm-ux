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
	$types = explode(',', $_REQUEST['type']);
	$start_date = $_REQUEST['start_date'];

	$res = array('success' => true);

	try {
		$events = array();
		if ($_REQUEST['image_id_field'] != "") {
			$image_id_field = $_REQUEST['image_id_field'];

			$events = \Civi\Api4\Event::get()
                ->addSelect('id', 'title', 'summary', 'description', 'event_type_id:label', 'start_date', 'end_date', 'file.uri', 'address.street_address', 'address.street_number', 'address.street_number_suffix', 'address.street_name', 'address.street_type', 'address.country_id:label')
                ->addJoin('File AS file', 'LEFT', ['file.id', '=', $image_id_field])
                ->addJoin('LocBlock AS loc_block', 'INNER', ['loc_block_id', '=', 'loc_block_id.id'])
                ->addJoin('Address AS address', 'LEFT', ['loc_block.address_id', '=', 'address.id'])
                ->addWhere('event_type_id:label', 'IN', $types)
                ->addWhere('start_date', '>', $start_date)
				->execute();

			$res['result'] = array();


			foreach ($events as $event) {
				$event_obj = array(
					'id' => $event['id'],
					'title' => $event['title'],
					'start' => date(DATE_ISO8601, strtotime($event['start_date'])),
					'end' => date(DATE_ISO8601, strtotime($event['end_date'])),
					'display' => 'auto',
					'startStr' => $event['start_date'],
					'endStr' => $event['end_date'],
					'url' => get_site_url() . '/civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Finfo&reset=1&id=' . $event['id'],
					'extendedProps' => array(
						'summary' => $event['summary'],
						'description' => $event['description'],
						'event_type' => $event['event_type_id:label'],
						'file.uri' => $event['file.uri'],
						'street_address' => $event['address.street_address'],
						'street_number' => $event['address.street_number'],
						'street_number_suffix' => $event['address.street_number_suffix'],
						'street_name' => $event['address.street_name'],
						'street_type' => $event['address.street_type'],
						'country' => $event['address.country_id:label'],
					)
				);
				array_push($res['result'], $event_obj);
			}
		} else {
			$events = \Civi\Api4\Event::get()
                ->addSelect('id', 'title', 'summary', 'description', 'event_type_id:label', 'start_date', 'end_date', 'address.street_address', 'address.street_number', 'address.street_number_suffix', 'address.street_name', 'address.street_type', 'address.country_id:label', 'is_online_registration')
                ->addJoin('LocBlock AS loc_block', 'INNER', ['loc_block_id', '=', 'loc_block_id.id'])
                ->addJoin('Address AS address', 'LEFT', ['loc_block.address_id', '=', 'address.id'])
                ->addWhere('event_type_id:label', 'IN', $types)
                ->addWhere('start_date', '>', $start_date)
				->execute();

			$res['result'] = array();


			foreach ($events as $event) {
				$event_obj = array(
					'id' => $event['id'],
					'title' => $event['title'],
					'start' => date(DATE_ISO8601, strtotime($event['start_date'])),
					'end' => date(DATE_ISO8601, strtotime($event['end_date'])),
					'display' => 'auto',
					'startStr' => $event['start_date'],
					'endStr' => $event['end_date'],
					'url' => get_site_url() . '/civicrm/?page=CiviCRM&q=civicrm%2Fevent%2Finfo&reset=1&id=' . $event['id'],
					'extendedProps' => array(
						'summary' => $event['summary'],
						'description' => $event['description'],
						'event_type' => $event['event_type_id:label'],
						'street_address' => $event['address.street_address'],
						'street_number' => $event['address.street_number'],
						'street_number_suffix' => $event['address.street_number_suffix'],
						'street_name' => $event['address.street_name'],
						'street_type' => $event['address.street_type'],
						'country' => $event['address.country_id:label'],
						'is_online_registration' => $event['is_online_registration']
					)
				);
				array_push($res['result'], $event_obj);
			}
                
        }
	} catch (CiviCRM_API4_Exception $e) {
		$res['err'] = $e;
	}

	echo json_encode($res);
	wp_die();

}

add_action( 'wp_ajax_get_events_all', 'get_events_all' );
add_action( 'wp_ajax_nopriv_get_events_all', 'get_events_all' );

run_civicrm_ux();
