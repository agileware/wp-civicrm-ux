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
		// Check if the form was just submitted, so we can hide the form if it has
		$form_submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

		// We still want to show the form if the previous submission was an invalid contact
		if ( $form_submitted && !empty($_POST['ss-cs-email']) ) {
			$contacts = \Civi\Api4\Contact::get( FALSE )
				->addSelect( 'id' )
				->addJoin( 'Email AS email', 'LEFT', ['email.contact_id', '=', 'id'] )
				->addWhere( 'email.email', '=', $_POST['ss-cs-email'] )
				->addGroupBy( 'id' )
				->execute();
			
			$form_submitted = count($contacts) > 0 ? true : false;
		}

		$displayInvalidMessage = false;
		// IF there is a valid CID and checksum in the  URL, display the content inside the shortcode
		if ( !$form_submitted && !empty( $_GET['cid'] ) && !empty( $_GET['cid'] ) ) {
			$cid = $_GET['cid'];
			$cs = $_GET['cs'];

			// Test if checksum is valid
			$isValid = $this->validateChecksum( $cid, $cs );

			if ( $isValid ) {
				return do_shortcode( $content );
			} else {
				$displayInvalidMessage = true;
			}
		}
    
		// Otherwise, display the self serve form

		// Get the current page URL
		$url = get_permalink();

		// Get the Self Serve Checksum settings
		$self_serve_checksum = Civicrm_Ux::getInstance()
			->get_store()
			->get_option('self_serve_checksum');
		
		$invalidMessage = $displayInvalidMessage ? '<p>That link has expired or is invalid. Please request a new link below.</p>' : '';
		$formText = wpautop( $self_serve_checksum['form_text'] );

		// Get the Cloudflare Turnstile
		$turnstile = $this->getTurnstile();
		$turnstile_passed = !empty($_POST['cf-turnstile-response']) && $this->verify_turnstile($_POST['cf-turnstile-response']);

		// Load scripts
		if ( !empty($turnstile) ) {
			wp_enqueue_script('turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true);
		}
		wp_enqueue_script('ss-cs-form', WP_CIVICRM_UX_PLUGIN_URL . WP_CIVICRM_UX_PLUGIN_NAME . '/public/js/self-serve-checksum.js', []);

        ob_start();

		?>
		<div class='ss-cs-status-message status'>
			<?php
			echo $invalidMessage;
			$this->self_serve_checksum_handle_form_submission();
			?>
		</div>
		<?php
		if ( !$form_submitted ) {
			echo $formText; ?>
			<form id="ss-cs-form" method="post" data-turnstilepassed="<?php echo $turnstile_passed ? 'true' : 'false'; ?>">
				<label for="ss-cs-email">Your email:</label>
				<input type="email" id="ss-cs-email" name="ss-cs-email" required>
				<input type="hidden" name="ss-cs-title" value="<?php echo get_the_title(); ?>">
				<input type="hidden" name="ss-cs-url" value="<?php echo $url; ?>">
				<?php echo $turnstile; ?>
				<button type="submit" name="ss-cs-submit" id="ss-cs-submit">Submit</button>
			</form>
			<?php
			if ( !empty($turnstile) ) { ?>
			<script>
				let turnstileCompleted = false;
				
				// This function will be called when Turnstile completes successfully
				function onTurnstileComplete(token) {
					turnstileCompleted = true;

					let submitButton = document.getElementById('ss-cs-submit');
					submitButton.disabled = false;
				}
			</script>
			<?php } ?>
		<?php } ?>
			
		<?php
        
        return ob_get_clean();
	}

	private function getTurnstile() {
		// Check if Cloudflare Turnstile Sitekey and Secret Key are provided.
		$civicrm_ux_cf_turnstile = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option('civicrm_ux_cf_turnstile');

		return !empty($civicrm_ux_cf_turnstile['sitekey']) && !empty($civicrm_ux_cf_turnstile['secret_key']) 
			? '<div class="cf-turnstile" data-sitekey="' . $civicrm_ux_cf_turnstile['sitekey'] . 
				'" data-theme="' . $civicrm_ux_cf_turnstile['theme'] . 
				'" data-callback="onTurnstileComplete"></div>'
			: '';
	}

	/**
	 * Validate the Checksum with the Contact ID in CiviCRM.
	 */
	private function validateChecksum( $cid, $cs ) {
		$results = \Civi\Api4\Contact::validateChecksum( FALSE )
			->setContactId( $cid )
			->setChecksum( $cs )
			->execute();
		
		return $results[0]['valid'];
	}

	/**
	 * Verify the Cloudflare Turnstile response.
	 */
	private function verify_turnstile($turnstile_response) {
		$civicrm_ux_cf_turnstile = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option('civicrm_ux_cf_turnstile');
		
		$secret = !empty($civicrm_ux_cf_turnstile['secret_key']) ? $civicrm_ux_cf_turnstile['secret_key'] : false;
		if ( !$secret ) {
			return $secret;
		}

		$response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
			'body' => array(
				'secret'   => $secret,
				'response' => $turnstile_response,
				'remoteip' => $_SERVER['REMOTE_ADDR'], // Optional, include user's IP address
			),
		));
	
		$response_body = wp_remote_retrieve_body($response);
		$result = json_decode($response_body, true);
	
		return isset($result['success']) && $result['success'];
	}

    // Handle form submission and send an email with the URL
	private function self_serve_checksum_handle_form_submission() {
		// First verify the turnstile
		// If the form doesn't have a turnstile, we can still continue
		$turnstilePassed = true;
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			// Check if Turnstile response exists
			if ( isset( $_POST['cf-turnstile-response'] ) && !empty( $_POST['cf-turnstile-response'] ) ) {
				$turnstile_response = sanitize_text_field($_POST['cf-turnstile-response']);
				
				if ( !$this->verify_turnstile( $turnstile_response ) ) {
					// Turnstile failed
					echo 'Turnstile verification failed, please try again.';
					$turnstilePassed = false;
				}
			} else if ( isset( $_POST['cf-turnstile-response'] ) && empty( $_POST['cf-turnstile-response'] ) ) {
				// Turnstile response is missing
				$turnstilePassed = false;
			}
		}

		if ( !$turnstilePassed ) {
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
			$url = trailingslashit( esc_url( $_POST['ss-cs-url'] ) );

			// Get contact cid
			$contacts = \Civi\Api4\Contact::get( FALSE )
				->addSelect( 'id' )
				->addJoin( 'Email AS email', 'LEFT', ['email.contact_id', '=', 'id'] )
				->addWhere( 'email.email', '=', $email )
				->addGroupBy( 'id' )
				->execute();

			/**
			 * WARNING 
			 * 
			 * There should only be one contact, but if there happens to be multiple (duplicate contacts), 
			 * this will send the email to the last cid.
			 * 
			 * The only true fix is for the duplicate contacts to be merged.
			 * 
			 */
			$cid = null;
			$cs = null;
			// Get the cid and generate a checksum
			foreach ( $contacts as $contact ) {
				$cid = $contact['id'];

				$checksums = \Civi\Api4\Contact::getChecksum( FALSE )
					->setContactId( $cid )
					->execute();

				$cs = $checksums[0]['checksum'];
			}

			// Build url with cid and checksum
			// `${URL}/?cid=${cid}&cs=${checksum}`
			$checksumUrl = $url . '?cid=' . $cid . '&cs=' . $cs;

			// Get the Self Serve Checksum settings
			$self_serve_checksum = Civicrm_Ux::getInstance()
				->get_store()
				->get_option( 'self_serve_checksum' );

			$submissionMessage = '';
			
			// Build and send the email to the contact.
			if ( $cid != null && $cs != null ) {
				$subject = get_bloginfo( 'name' ) . ' - ' . $pageTitle . ' link';

				// Apply filters to alter the email subject
				$subject = apply_filters( 'ux_self_serve_checksum_email_subject', $subject, $pageTitle );
				
				// Apply <p> tags to the email message, then do token replacements
				$message = wpautop( $self_serve_checksum['email_message'] );

				$tokenData = [
					'page_title' => $pageTitle,
					'checksum_url' => $checksumUrl,
				];
				$message = $this->ss_cs_replace_custom_tokens($message, $tokenData);

				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				wp_mail( $email, $subject, $message, $headers );

				$submissionMessage = wpautop( $self_serve_checksum['form_confirmation_text'] );
			} else {
				// No valid contact was found
				$submissionMessage = wpautop( $self_serve_checksum['form_invalid_contact_text'] );
			}
			
			$tokenData = [
				'page_title' => $pageTitle,
				'email_address' => $email,
			];
			$submissionMessage = $this->ss_cs_replace_custom_tokens($submissionMessage, $tokenData);

			echo $submissionMessage;
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