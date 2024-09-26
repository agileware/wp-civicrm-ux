<?php

/**
 * Interface iCivicrm_Ux_Shortcode
 */
abstract class Abstract_Civicrm_Ux_Shortcode implements iCivicrm_Ux_Managed_Instance {
	protected $manager;

	/**
	 * @param \Civicrm_Ux_Shortcode_Manager $manager
	 */
	public function __construct( $manager ) {
		$this->manager = $manager;
	}

	/**
	 * @return string The name of shortcode
	 */
	abstract public function get_shortcode_name();

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	abstract public function shortcode_callback( $atts = [], $content = null, $tag = '' );

	/**
	 * Disabling wpautop for shortcode content may not strip out the unwanted tags correctly.
	 * Optionally clean shortcode content via regex.
	 * 
	 * @param string $content
	 * 
	 * @return string The cleaned content of the shortcode
	 */
	public function clean_content_output($content = '') {
		// Default regex patterns to remove empty and misconfigured <p> tags and <br> tags
		$default_regex = '/<p>(\s*&nbsp;\s*|\s*)<\/p>|<\/p>\s*<p>|<br\s*\/?>/';
    
		// Apply a filter so users can change the regex pattern
		$custom_regex = apply_filters('ux_shortcode_clean_content_output_regex', $default_regex);

		$cleaned = preg_replace($custom_regex, '', $content);

		return $cleaned;
	}
}