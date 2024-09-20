<?php

/**
 * WIP
 *
 * Class Civicrm_Ux_Option_Store
 * 
 * NOTE: Register the setting in class-civicrm-ux-admin.php.
 */
class Civicrm_Ux_Option_Store {

	/**
	 * array[option name]
	 *      ['instance']
	 *      ['default']
	 *
	 * @var array
	 */
	protected $options;

	public function __construct() {
		$this->options = [];

		// For membership
		$this->register_option( 'civicrm_summary_options', NULL, [
			'civicrm_summary_show_renewal_date'    => '30',
			'civicrm_summary_membership_join_URL'  => '/join/',
			'civicrm_summary_membership_renew_URL' => '/renew/',
		] );

		// For contributions
		$this->register_option( 'civicrm_contribution_ux', NULL, [
			'is_recur_default'     => FALSE,
			'is_autorenew_default' => FALSE,
		] );

		// For Plugins we wish to block
		$this->register_option( 'civicrm_plugin_activation_blocks', NULL, [
			'event_tickets' => TRUE,
		] );

		// Define the path to the template files
		$template_dir = WP_CIVICRM_UX_PLUGIN_PATH . 'templates/self-serve-checksum/';
	
		// Load the default content 
		$defaults = [];

		$fields = [
			'form_text' => 'ss-cs-form-text.html',
			'form_confirmation_text' => 'ss-cs-form-confirmation-text.html',
			'form_invalid_contact_text' => 'ss-cs-form-invalid-contact-text.html',
			'email_message' => 'ss-cs-email-message.html',
		];

		foreach ($fields as $field => $file) {
			$defaults[$field] = file_exists($template_dir . $file) 
				? file_get_contents($template_dir . $file) 
				: '';
		}

		// For Self Serve Checksum Forms
		$this->register_option( 'self_serve_checksum', NULL, [
			'form_text' => $defaults['form_text'],
			'form_confirmation_text' => $defaults['form_confirmation_text'],
			'form_invalid_contact_text' => $defaults['form_invalid_contact_text'],
			'email_message' => $defaults['email_message'],
		] );

		// For Cloudflare Turnstiles
		$this->register_option( 'civicrm_ux_cf_turnstile', NULL, [
			'sitekey' => '',
			'secret_key' => '',
			'theme'	=> 'auto',
		] );
	}

	public function register_option( $name, $instance, $default = '' ) {
		if ( in_array( $name, array_keys( $this->options ) ) ) {
			// TODO throw exception?
			return;
		}

		$this->options[ $name ] = [
			'instance' => $instance,
			'default'  => $default,
		];
	}

	public function get_option( $name ) {
		return get_option( $name, $this->options[ $name ]['default'] );
	}

	public function update_option( $name, $value, $autoload ) {
		return update_option( $name, $value, $autoload );
	}

	public function delete_option( $name ) {
		return delete_option( $name );
	}

	public function get_options() {
		return $this->options;
	}

}