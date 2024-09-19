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

        $error_message =    '<div class="event-cancellation-error" style="display:none;">
                                <p><span class="title">Something went wrong with your request.</span></p>
                                <p>Please wait a few minutes before trying again. If the problem persists, please contact us for assistance.</p>
                            </div>';

        return '<div id="app" class="two-col-app"></div><script>let app_wp_nonce = \'' . esc_js($nonce) . '\';</script>' . $error_message;
	}
}
