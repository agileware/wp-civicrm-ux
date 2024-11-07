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
 * Version:           1.20.3
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Requires Plugins:  civicrm
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

define( 'WP_CIVICRM_UX_PLUGIN_NAME', basename(plugin_dir_path(__FILE__ )) );
define( 'WP_CIVICRM_UX_PLUGIN_URL', plugins_url('', dirname(__FILE__) ) . '/');
define( 'WP_CIVICRM_UX_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_CIVICRM_UX_PLUGIN_GITHUB_REPO', 'agileware/wp-civicrm-ux' ); // GitHub username and repo

// Hide Upsell to install the Events Tickets plugin
if ( !defined( 'TEC_HIDE_UPSELL' ) ) {
	define( 'TEC_HIDE_UPSELL', true );
}

require_once WP_CIVICRM_UX_PLUGIN_PATH . 'vendor/autoload.php';

// Include the upgrader class
require_once WP_CIVICRM_UX_PLUGIN_PATH . 'includes/class-civicrm-ux-upgrader.php';

// Initialize the upgrader
$upgrader = new Civicrm_Ux_Upgrader( __FILE__ );
$upgrader->init();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-civicrm-ux-activator.php
 */
function activate_civicrm_ux() {
	require_once WP_CIVICRM_UX_PLUGIN_PATH . 'includes/class-civicrm-ux-activator.php';
	Civicrm_Ux_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-civicrm-ux-deactivator.php
 */
function deactivate_civicrm_ux() {
	require_once WP_CIVICRM_UX_PLUGIN_PATH . 'includes/class-civicrm-ux-deactivator.php';
	Civicrm_Ux_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_civicrm_ux' );
register_deactivation_hook( __FILE__, 'deactivate_civicrm_ux' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WP_CIVICRM_UX_PLUGIN_PATH . 'includes/class-civicrm-ux.php';

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

run_civicrm_ux();
