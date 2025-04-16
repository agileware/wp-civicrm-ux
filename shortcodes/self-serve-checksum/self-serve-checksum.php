<?php

class Civicrm_Ux_Shortcode_Self_Serve_Checksum extends Abstract_Civicrm_Ux_Shortcode {

	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_self_serve_checksum';
	}

	/**
	 * @param array $atts
	 * @param null $content
	 * @param string $tag
	 *
	 * @return mixed Should be the html output of the shortcode
	 */
	public function shortcode_callback( $atts = [], $content = null, $tag = '' ) {
		// If the user is logged in AND has a CiviCRM contact, display the content inside the shortcode
		if ( CRM_Core_Session::singleton()->getLoggedInContactID() !== null ) {
			return do_shortcode( shortcode_unautop($content) );
		}

		// Check if the form was just submitted, so we can hide the form if it has
		$form_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

		// We still want to show the form if the previous submission was an invalid contact
		if ( $form_submitted && !empty($_POST['ss-cs-email']) ) {
			$email = sanitize_email( $_POST['ss-cs-email'] );

			$contacts = \Civi\Api4\Contact::get( FALSE )
				->addSelect( 'id' )
				->addJoin( 'Email AS email', 'LEFT', ['email.contact_id', '=', 'id'] )
				->addWhere( 'email.email', '=', $email )
				->addWhere( 'contact_type', '=', 'Individual')
				->addGroupBy( 'id' )
				->execute();
			
			$form_submitted = count($contacts) > 0 ? true : false;
		}

		// If there is a valid CID and checksum in the URL, display the content inside the shortcode
		$displayInvalidMessage = false;
		$urlParamsKeys = array_change_key_case($_GET, CASE_LOWER);
		if ( !empty( $urlParamsKeys['cid'] ) && !empty( $urlParamsKeys['cs'] ) ) {
			$cid = $urlParamsKeys['cid'];
			$cs = $urlParamsKeys['cs'];

			// Test if checksum is valid
			$isValid = Civicrm_Ux_Contact_Utils::validate_checksum( $cid, $cs );

			if ( $isValid ) {
				return do_shortcode( shortcode_unautop($content) );
			} else {
				$displayInvalidMessage = true;
			}
		}
		$invalidMessage = $displayInvalidMessage ? '<p>That link has expired or is invalid. Please request a new link below.</p>' : '';
    
		// Otherwise, display the self serve form

		// Get the current page URL
		$url = $this->get_protected_page_url();

		// Get the Self Serve Checksum settings
		$self_serve_checksum = get_option( 'self_serve_checksum', [] );
		
		$formText = wpautop( $self_serve_checksum['form_text'] );

		// Get the Cloudflare Turnstile
		$turnstile = $this->getTurnstile();
		$turnstile_passed = !empty($_POST['cf-turnstile-response']) && $this->verify_turnstile($_POST['cf-turnstile-response']);

		// Load scripts
		if ( !empty($turnstile) ) {
			wp_enqueue_script('turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true);
		}
		wp_enqueue_script('ss-cs-form', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/js/self-serve-checksum.js', []);

		$args = [
			'form_submitted' => $form_submitted,
			'form_text' => $formText,
			'url' => $url,
			'invalidMessage' => $invalidMessage,
		];

		if ( !empty( $turnstile ) ) {
			$args = array_merge($args, [
				'turnstile' => $turnstile,
				'turnstile_passed' => $turnstile_passed,
			]);
		}

        ob_start();
		?>
		<div class='ss-cs-status-message status'>
			<?php
			echo $args['invalid_message'];
			// Pass along whether or not the turnstile check was passed at this point, so we won't have to check again.
			$this->self_serve_checksum_handle_form_submission($turnstile_passed);
			?>
		</div>
		<?php
		civicrm_ux_load_template_part( 'shortcode', 'self-serve-checksum', array_merge($args) );
        
        return ob_get_clean();
	}

	private function getTurnstile() {
		// Check if Cloudflare Turnstile Sitekey and Secret Key are provided.
		$civicrm_ux_cf_turnstile = get_option( 'civicrm_ux_cf_turnstile', [] );

		return !empty($civicrm_ux_cf_turnstile['sitekey']) && !empty($civicrm_ux_cf_turnstile['secret_key']) 
			? '<div class="cf-turnstile" data-sitekey="' . $civicrm_ux_cf_turnstile['sitekey'] . 
				'" data-theme="' . $civicrm_ux_cf_turnstile['theme'] . 
				'" data-callback="onTurnstileComplete"></div>'
			: '';
	}

	/**
	 * Verify the Cloudflare Turnstile response.
	 */
	private function verify_turnstile($turnstileResponse) {
		$civicrm_ux_cf_turnstile = get_option( 'civicrm_ux_cf_turnstile', [] );
		
		$secret = !empty($civicrm_ux_cf_turnstile['secret_key']) ? $civicrm_ux_cf_turnstile['secret_key'] : false;
		if ( !$secret ) {
			return $secret;
		}

		$response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
			'body' => array(
				'secret'   => $secret,
				'response' => $turnstileResponse,
				'remoteip' => $_SERVER['REMOTE_ADDR'], // Optional, include user's IP address
			),
		));
	
		$body = wp_remote_retrieve_body($response);
		$result = json_decode($body, true);
	
		return isset($result['success']) && $result['success'];
	}

	/**
	 * Get the url of the page protected by the Self Serve Checksum form.
	 */
	private function get_protected_page_url( $url = null ) {
		global $wp;

		// Get the current page URL
		$url = $url ?? add_query_arg( $_GET, home_url( $wp->request ) );

		$removeArgs = ['page', 'pagename', 'cs'];
		$queryArgs = $this->remove_query_args_case_insensitive($url, $removeArgs);

		// Rebuild the URL without the case-insensitive query parameters
		$url = add_query_arg( $queryArgs, home_url( $wp->request ) );

		return $url;
	}

	/**
	 * Removes URL query parameters that we don't want to pass on through the email.
	 */
	private function remove_query_args_case_insensitive($url, $removeArgs) {
		$parsedUrl = wp_parse_url($url);

		if ( isset($parsedUrl['query']) ) {
			parse_str($parsedUrl['query'], $queryArgs);

			$queryArgs = array_change_key_case($queryArgs, CASE_LOWER);

			// Remove the normalized parameters
			foreach ( $removeArgs as $arg ) {
				if ( isset( $queryArgs[ strtolower( $arg ) ] ) ) {
					unset( $queryArgs[ $arg ] );
				}
			}
		
			// Output the cleaned URL
			return $queryArgs;
		}

		return [];
	}

	function get_base_url($url) {
		// Parse the URL into its components
		$parsedUrl = parse_url($url);
	
		// Rebuild the base URL (scheme, host, and path)
		$scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
		$host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
		$path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
	
		// Concatenate the parts to form the base URL
		return $scheme . $host . $path;
	}

    // Handle form submission and send an email with the URL
	private function self_serve_checksum_handle_form_submission($turnstile_passed = false) {
		// First verify the turnstile
		// $turnstile_passed argument is the result when the turnstile was verified on page load, since
		// the shortcode_callback occurs before handling form submission.

		if ( !$turnstile_passed && isset( $_POST['cf-turnstile-response'] ) && !empty( $_POST['cf-turnstile-response'] ) ) {
			// Turnstile failed
			echo 'Turnstile verification failed, please try again.';
		} else if ( isset( $_POST['cf-turnstile-response'] ) && empty( $_POST['cf-turnstile-response'] ) ) {
			// Turnstile response is missing
			$turnstile_passed = false;
		} else if ( !isset( $_POST['cf-turnstile-response'] ) ) {
			// If the form doesn't have a turnstile, we can still continue
			$turnstile_passed = true;
		}

		if ( !$turnstile_passed ) {
			return;
		}

		if ( !empty( $_POST['ss-cs-email'] ) ) {
			$email = sanitize_email( $_POST['ss-cs-email'] );

			// Exit early if we have an invalid email
			if ( !is_email($email) ) {
				echo '<p>Please enter a valid email address.</p>';
				return;
			}

			$pageTitle = sanitize_text_field( $_POST['ss-cs-title'] );
			$parsedUrl = wp_parse_url( $_POST['ss-cs-url'] ); // To get additional info from the URL parameters if provided
			$url = esc_url( $this->get_base_url( $_POST['ss-cs-url'] ) );

			// Get the Self Serve Checksum settings
			$self_serve_checksum_setting = get_option( 'self_serve_checksum', [] );

			/**
			 * WARNING 
			 * 
			 * There should only be one contact, but if there happens to be multiple (duplicate contacts), 
			 * this will send the email to the first cid.
			 * 
			 * The only true fix is for the duplicate contacts to be merged.
			 * 
			 * 	- If cid is provided, check for a contact with that cid and email combination.
			 * 	- If they don't match, find a contact with that email.
			 * 		- If multiple contacts found, return the oldest one, i.e. the lowest cid. We are ASSUMING this is the correct one.
			 */
			
			if ( isset( $parsedUrl['query'] ) ) {
				parse_str($parsedUrl['query'], $queryArgs);
				$queryArgs = array_change_key_case($queryArgs, CASE_LOWER);

				// Remove the normalized parameters
				if ( isset( $queryArgs['cid'] ) ) {
					// Check if a contact exists with the same contact id and email address
					$cid = \Civi\Api4\Contact::get( FALSE )
						->addSelect( 'id' )
						->addJoin( 'Email AS email', 'LEFT', ['email.contact_id', '=', 'id'] )
						->addWhere( 'email.email', '=', $email )
						->addWhere( 'id', '=', $queryArgs['cid'] )
						->addWhere( 'contact_type', '=', 'Individual')
						->execute()
						->first()['id'];

					unset($queryArgs['cid']);
				}
			}

			if ( empty($cid) ) {
				// cid and email mismatch. Look for a contact just by email.
				$cid = \Civi\Api4\Contact::get( FALSE )
					->addSelect( 'id' )
					->addJoin( 'Email AS email', 'LEFT', ['email.contact_id', '=', 'id'] )
					->addWhere( 'email.email', '=', $email )
					->addWhere( 'contact_type', '=', 'Individual')
					->addOrderBy('id', 'ASC')
					->execute()
					->first()['id'];
			}

			// If still empty, display an error message 
			if ( empty( $cid ) ) {
				// No valid contact was found, that contact record doesn't exist in our CiviCRM Installation
				$submissionMessage = wpautop( $self_serve_checksum_setting['form_invalid_contact_text'] );
				
				$tokenData = [
					'page_title' => $pageTitle,
					'email_address' => $email,
				];
				echo $this->ss_cs_replace_custom_tokens($submissionMessage, $tokenData);
				return;
			}

			// Get a checksum
			$cs = \Civi\Api4\Contact::getChecksum( FALSE )
					->setContactId( $cid )
					->execute()
					->first()['checksum'];
			
			// Build and send the email to the contact.
			if ( !empty( $cs ) ) {
				// Build url with cid and checksum
				// `${URL}/?{maybeotherargs}&cid=${cid}&cs=${checksum}`
				$queryArgs['cid'] = $cid;
				$queryArgs['cs'] = $cs;
				$checksumUrl = add_query_arg( $queryArgs, $url );
				
				$subject = get_bloginfo( 'name' ) . ' - ' . $pageTitle . ' link';

				// Apply filters to alter the email subject
				$subject = apply_filters( 'ux_self_serve_checksum_email_subject', $subject, $pageTitle );
				
				// Apply <p> tags to the email message, then do token replacements
				$message = wpautop( $self_serve_checksum_setting['email_message'] );

				$tokenData = [
					'page_title' => $pageTitle,
					'checksum_url' => $checksumUrl,
				];
				$message = $this->ss_cs_replace_custom_tokens($message, $tokenData);

				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				wp_mail( $email, $subject, $message, $headers );

				$submissionMessage = wpautop( $self_serve_checksum_setting['form_confirmation_text'] );

				$tokenData = [
					'page_title' => $pageTitle,
					'email_address' => $email,
				];
				echo $this->ss_cs_replace_custom_tokens($submissionMessage, $tokenData);
			}
		}
	}

	private function ss_cs_replace_custom_tokens($content, $tokenData = [] ) {
		// Define custom tokens and their replacements
		$tokens = array(
			'{page_title}' => isset( $tokenData['page_title'] ) ? $tokenData['page_title'] : '{page_title}',
			'{email_address}' => isset( $tokenData['email_address'] ) ? $tokenData['email_address'] : '{email_address}',
			'{checksum_url}' => isset( $tokenData['checksum_url'] ) ? $tokenData['checksum_url'] : '{checksum_url}',
			// Add more tokens and their replacements as needed
		);
	
		// Replace tokens in the content
		$content = str_replace(array_keys($tokens), array_values($tokens), $content);
	
		return $content;
	}
}