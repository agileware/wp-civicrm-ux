<?php

/**
 * Helper class
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 */

/**
 * Helper class
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Civicrm_Ux_Helper {

	/**
	 * @var \Civicrm_Ux $plugin the plugin instance
	 */
	protected $plugin;

	/**
	 * Initialize
	 *
	 * @param \Civicrm_Ux $plugin
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	public function generate_hash() {
		return bin2hex( random_bytes( 16 ) );
	}

	public function check_hash_in_option( string $hash, string $option ) {
		return $hash == get_option( $option );
	}

	public function ends_with( $string, $needle ) {
		$str_len    = strlen( $string );
		$needle_len = strlen( $needle );
		if ( $needle_len > $str_len ) {
			return false;
		}

		return substr_compare( $string, $needle, $str_len - $needle_len, $needle_len ) === 0;
	}
}
