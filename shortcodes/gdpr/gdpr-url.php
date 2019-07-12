<?php

class Civicrm_Ux_Shortcode_Gdpr_Url extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'agileware_utility_shortcode_custom_gdpr_url';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		$commPrefURL = '#';

		if ( civicrm_initialize() ) {
			try {

				$cid = CRM_Core_Session::singleton()->getLoggedInContactID();
				//This plugin requires CiviCRM GDPR: https://github.com/veda-consulting/uk.co.vedaconsulting.gdpr
				$commPrefURL = CRM_Gdpr_CommunicationsPreferences_Utils::getCommPreferenceURLForContact( $cid );

			} catch ( CiviCRM_API3_Exception $e ) {

				if ( isset( $cid ) ) {
					error_log( "Unable to get GDPR url for contact id: " . $cid );
				} else {

					error_log( "Could not resolve contact id" );

				}

				if ( ! isset( $gdpr_extension_dir ) ) {
					error_log( "Failed to obtain the GDPR extension path" );

				}

				return '#';

			}

		}

		return $commPrefURL;
	}
}