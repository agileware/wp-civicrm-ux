<?php

use Civi\Api4\Event;

/**
 * Outputs the button to trigger event self-cancellation. Also outputs the corresponding confirmation modal dialog.
 * To be used in listings.
 */
class Civicrm_Ux_Shortcode_Event_CancelRegistration_Button extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_cancelregistration_button';
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

		// Ensure the button is only rendered for the current logged in user
		$cid = CRM_Core_Session::singleton()->getLoggedInContactID();
		$participant = \Civi\Api4\Participant::get(FALSE)
				->addWhere('event_id', '=', $atts['eventid'])
				->addWhere('contact_id', '=', $cid)
				->execute();

		if ( count( $participant ) == 0 ) {
			return '';
		}

		$event = \Civi\Api4\Event::get(FALSE)
				->addWhere('id', '=', $atts['eventid'])
				->execute();

		$confirmation_dialog = '<dialog id="event-cancellation-confirm-dialog-' . $atts['eventid'] . '" class="event-cancellation-confirm-dialog">
                                    <div class="modal-content">
										<p><span class="heading">' . $event[0]['title'] . '</span></p>
                                        <p>Are you sure you want to cancel this event registration?</p>
										<div class="modal-buttons">
											<button class="confirm-yes">Yes</button>
                                        	<button class="confirm-no">No</button>
										</div>
                                    </div>
                                </dialog>';

		return '<div class="event-links"><button data-eventId="' . $atts['eventid'] . '" class="event-cancel-registration">' . $atts['text'] . '</button>' . $confirmation_dialog . '</div>';
	}
}
