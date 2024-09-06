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

		// Define the path to your template files
		$template_dir = WP_CIVICRM_UX_PLUGIN_PATH . 'templates/';
    
		// Load default values from files
		$defaults = [];
	
		// Load the default content for event tickets from a template file
		$defaults['form_text'] = file_exists($template_dir . 'self-serve-checksum-form-text.html') 
			? file_get_contents($template_dir . 'self-serve-checksum-form-text.html') 
			: '';

		$defaults['email_message'] = file_exists($template_dir . 'self-serve-checksum-email-message.html') 
			? file_get_contents($template_dir . 'self-serve-checksum-email-message.html') 
			: '';

		// For Self Serve Checksum Forms
		$this->register_option( 'self_serve_checksum', NULL, [
			'form_text' => $defaults['form_text'],
			'email_subject' => "Continue with your form submission",
			'email_message' => $defaults['email_message'],
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