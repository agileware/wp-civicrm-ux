<?php

use Civi\Api4\Event;

/**
 * Outputs a button to a given link. Can be used to add a button that is shown conditionally, as long as 
 * the link is available, or if the fallback exists.
 */
class Civicrm_Ux_Shortcode_Custom_Button extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_custom_button';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		// normalize attribute keys, lowercase
		$atts = array_change_key_case( (array) $atts, CASE_LOWER );

        // Override default attributes with user attributes
        $mod_atts = shortcode_atts([
            'text' => 'Go',
            'url' => '',
            'fallback_url' => '',
            'url_params' => '',
            'css_classes' => '',
        ], $atts, $tag);

        $mod_atts['url'] = !empty($mod_atts['url']) ? $mod_atts['url'] : $mod_atts['fallback_url'];

        // if the url is still empty, don't output the button
        if ( empty($mod_atts['url']) ) {
            return '';
        } 

        // Append the URL parameters
        $mod_atts['url'] = trailingslashit($mod_atts['url']) . $mod_atts['url_params'];

		return '<a href="' . $mod_atts['url'] . '" class="button wp-element-button ' . $mod_atts['css_classes'] . '">' . $mod_atts['text'] . '</a>';
	}
}
