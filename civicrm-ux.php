<?php

/**
 * @link              https://agileware.com.au
 * @since             1.0.0
 * @package           Civicrm_Ux
 *
 * @wordpress-plugin
 * Plugin Name:       CiviCRM UX
 * Plugin URI:        https://agileware.com.au
 * Description:       UX for CiviCRM
 * Version:           0.0.5
 * Author:            Agileware
 * Author URI:        https://agileware.com.au/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       civicrm-ux
 * Domain Path:       /languages
 * GitHub Plugin URI: todo
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//TODO check CiviCRM and Caldera Forms activated

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'CIVICRM_UXVERSION', '0.0.5' );

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

	$plugin = new Civicrm_Ux();
	$plugin->run();

}

run_civicrm_ux();