<?php

/**
 * Helper class
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/includes
 */

/**
 * Helper class
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/includes
 * @author     Agileware <support@agileware.com.au>
 */
class Agileware_Civicrm_Utilities_Helper {

	/**
	 * @var \Agileware_Civicrm_Utilities $plugin the plugin instance
	 */
	protected $plugin;

	/**
	 * Initialize
	 *
	 * @param \Agileware_Civicrm_Utilities $plugin
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * @param string $directory The relative directory
	 *
	 * @return array Array of paths (string)
	 */
	public function get_php_file_paths( $directory ) {
		$file_paths = [];
		foreach ( scandir( plugin_dir_path( dirname( __FILE__ ) ) . "/$directory" ) as $filename ) {
			$path = plugin_dir_path( dirname( __FILE__ ) ) . '/' . $directory . '/' . $filename;
			if ( $this->is_php_file( $path ) ) {
				$file_paths[] = $path;
			}
		}

		return $file_paths;
	}

	/**
	 * @param string $path absolute path
	 *
	 * @return bool false if failed
	 */
	public function require_php_file( $path ) {
		if ( $this->is_php_file( $path ) ) {
			require_once $path;

			return TRUE;
		}

		return FALSE;
	}

	public function generate_hash() {
		return bin2hex( random_bytes( 16 ) );
	}

	public function check_hash_in_option( string $hash, string $option ) {
		return $hash == get_option( $option );
	}

	public function ends_with($string, $needle) {
		$str_len = strlen($string);
		$needle_len = strlen($needle);
		if ($needle_len > $str_len) return false;
		return substr_compare($string, $needle, $str_len - $needle_len, $needle_len) === 0;
	}

	/**
	 * @param string $path absolute path
	 *
	 * @return bool false if the path is not a php file
	 */
	private function is_php_file( $path ) {
		$file_info = pathinfo( $path );

		return is_file( $path ) && $file_info['extension'] == 'php';
	}

	private function is_sub_dir( $path ) {
		$file_info = pathinfo( $path );

		return is_dir( $path ) && in_array( $file_info['filename'], [ '.', '..' ] );
	}
}
