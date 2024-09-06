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
		// IF there is a valid CID and checksum in the  URL, display the content inside the shortcode
		if ( isset( $_GET['cid'] ) && !empty( $_GET['cid'] ) && isset( $_GET['cid'] ) && !empty( $_GET['cid'] ) ) {
			$cid = $_GET['cid'];
			$cs = $_GET['cs'];

			// Test if checksum is valid
			$isValid = $this->validateChecksum( $cid, $cs );

			if ( $isValid ) {
				return do_shortcode( $content );
			}
		}
    
		// Otherwise, display the self serve form

		// Define shortcode tags
		$atts = shortcode_atts(
			array(
				'mid' => false, // Does not need a value
			),
			$atts
		);

		// Check if the 'mid' tag exists and is not false. Can just be present without a value to be considered true.
		$mid = ( !empty( $atts['mid'] ) && array_key_exists( 'mid', $atts ) && ( $atts['mid'] == null || filter_var( $atts['mid'], FILTER_VALIDATE_BOOLEAN ) != false ) ) 
				? '<input type="hidden" name="ss-cs-mid" value="true">' 
				: null;

		// Get the current page URL
		$url = get_permalink();

		// Get the Self Serve Checksum settings
		$self_serve_checksum = Civicrm_Ux::getInstance()
			->get_store()
			->get_option('self_serve_checksum');
		
		$formText = wpautop( $self_serve_checksum['form_text'] );

        ob_start();
        ?>

		<?php echo $formText; ?>
        <form id="ss-cs-form" method="post">
            <label for="ss-cs-email">Enter your email:</label>
            <input type="email" id="ss-cs-email" name="ss-cs-email" required>
			<input type="hidden" name="ss-cs-title" value="<?php echo get_the_title(); ?>">
            <input type="hidden" name="ss-cs-url" value="<?php echo $url; ?>">
			<?php echo $mid; ?>
            <button type="submit" name="ss-cs-submit">Submit</button>
        </form>

		<?php
        $this->self_serve_checksum_handle_form_submission();
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
			$pageTitle = sanitize_text_field( $_POST['ss-cs-title'] );
			$url = trailingslashit( esc_url( $_POST['ss-cs-url'] ) );

			// Get contact cid and checksom from civicrm via api calls
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

			// TODO handle membership(s) checksums
			$checksumURLs_memberships = [];
			if ( $_POST['ss-cs-mid'] ) {
				$memberships = \Civi\Api4\Membership::get( FALSE )
					->addWhere( 'contact_id', '=', $cid )
					->execute();
				
				foreach ( $memberships as $membership ) {
					$checksumURLs_memberships[] = $checksumUrl . '&mid=' . $membership['id'];
				}
			}

			// Build and send the email to the contact.
			if ( is_email( $email ) ) {
				// Get the Self Serve Checksum settings
				$self_serve_checksum = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option( 'self_serve_checksum' );
				
				$subject = $self_serve_checksum['email_subject'] . ' - ' . $pageTitle;
				$message = wpautop( $self_serve_checksum['email_message'] );

				// Append the checksum URL(s) to the message
				$link = '<br><br><a href="' . $checksumUrl . '">Return to ' . $pageTitle . '</a>';

				// Apply filters to alter the link content
				$link = apply_filters( 'self_serve_checksum_email_link', $link, $checksumUrl, $pageTitle );

				$message .= $link;

				$headers = array( 'Content-Type: text/html; charset=UTF-8' );

				wp_mail( $email, $subject, $message, $headers );

				$confirmation = '<p>Thank you! An email has been sent to your address with a link to complete this form.</p>';

				// Apply filters to alter the confirmation message
				$confirmation = apply_filters( 'self_serve_checksum_confirmation_message', $confirmation, $pageTitle );

				echo $confirmation;
			} else {
				echo '<p>Please enter a valid email address.</p>';
			}
		}
	}
}