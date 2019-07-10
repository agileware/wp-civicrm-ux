<?php

/**
 * Fired during plugin activation
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( ! self::check_dependency() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Please install and Activate CiviCRM.', 'civicrm-ux' ), 'Plugin dependency check', array( 'back_link' => true ) );
		}
	}

	public static function check_dependency() {
		$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

		return in_array( 'civicrm/civicrm.php', $active_plugins );
	}
}
