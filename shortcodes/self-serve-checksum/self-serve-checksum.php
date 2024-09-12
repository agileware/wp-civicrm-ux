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
		$displayInvalidMessage = false;
		// IF there is a valid CID and checksum in the  URL, display the content inside the shortcode
		if ( isset( $_GET['cid'] ) && !empty( $_GET['cid'] ) && isset( $_GET['cid'] ) && !empty( $_GET['cid'] ) ) {
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

		// Check if the form was submitted, so we can hide the form if it has
		$form_submitted = isset($_POST['ss-cs-submit']);
		
		if ( $form_submitted && isset($_POST['ss-cs-email']) ) {
			// We still want to show the form if the previous submission was an invalid contact
			$contacts = \Civi\Api4\Contact::get( FALSE )
				->addSelect( 'id' )
				->addJoin( 'Email AS email', 'LEFT', ['email.contact_id', '=', 'id'] )
				->addWhere( 'email.email', '=', $_POST['ss-cs-email'] )
				->addGroupBy( 'id' )
				->execute();
			
			$form_submitted = count($contacts) > 0 ? true : false;
		}
		var_dump($_POST);

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
			<form id="ss-cs-form" method="post">
				<label for="ss-cs-email">Your email:</label>
				<input type="email" id="ss-cs-email" name="ss-cs-email" required>
				<input type="hidden" name="ss-cs-title" value="<?php echo get_the_title(); ?>">
				<input type="hidden" name="ss-cs-url" value="<?php echo $url; ?>">
				<button type="submit" name="ss-cs-submit" id="ss-cs-submit">Submit</button>
			</form>
		<?php } ?>

		<?php
        
        return ob_get_clean();
	}

	private function validateChecksum( $cid, $cs ) {
		$results = \Civi\Api4\Contact::validateChecksum( FALSE )
			->setContactId( $cid )
			->setChecksum( $cs )
			->execute();
		
		return $results[0]['valid'];
	}

    // Handle form submission and send an email with the URL
	private function self_serve_checksum_handle_form_submission() {
		if ( isset( $_POST['ss-cs-submit'] ) && !empty( $_POST['ss-cs-email'] ) ) {
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