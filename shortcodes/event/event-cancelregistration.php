<?php

use Civi\Api4\Event;

/**
 * Outputs the necessary nonce.
 */
class Civicrm_Ux_Shortcode_Event_CancelRegistration extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_cancelregistration';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		$nonce = wp_create_nonce('wp_rest');

        return '<div id="app" class="two-col-app"></div><script>let app_wp_nonce = \'' . esc_js($nonce) . '\';</script>';
	}
}
