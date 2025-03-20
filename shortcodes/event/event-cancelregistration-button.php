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

		// Override default attributes with user attributes
        $mod_atts = shortcode_atts([
            'text' => 'Cancel My Registration',
            'eventid' => null,
			'css_classes' => '',
			'icon_classes' => 'fa fa-times',
        ], $atts, $tag);

		$cid = CRM_Core_Session::singleton()->getLoggedInContactID();

		// Make sure the current logged in user has permission to cancel their registration.
		// This should match up to the permission set in the cancel_event_registration Form Processor,
		// OR default to the 'register for events' permission
		$formProcessorInstance = \Civi\Api4\FormProcessorInstance::get(FALSE)
			->addSelect('permission')
			->addWhere('name', '=', 'cancel_event_registration')
			->execute();
		
		// Do not output anything if we couldn't find the Form Processor
		if ( count( $formProcessorInstance ) == 0 ) {
			return '';
		}
		
		// If a permission is set on the FormProcessor, check that the user has that permission first
		if ( isset( $formProcessorInstance[0]['permission'] ) && !CRM_Core_Permission::check($formProcessorInstance[0]['permission']) ) {
			return '';
		} else if ( !isset( $formProcessorInstance[0]['permission'] ) && !CRM_Core_Permission::check('register for events') ) {
			// Otherwise default to check for the 'register for events' permission
			return '';
		}

		// Ensure the button is only rendered for the current logged in user, when they have an active registration
		$participant = \Civi\Api4\Participant::get(FALSE)
				->addJoin('ParticipantStatusType AS participant_status_type', 'LEFT')
				->addWhere('event_id', '=', $mod_atts['eventid'])
				->addWhere('contact_id', '=', $cid)
				->addWhere('participant_status_type.class', '!=', 'Negative')
				->execute();

		if ( count( $participant ) == 0 ) {
			return '';
		}

		// Only display this button for events that have not already finished.
        $today = current_time('mysql');
		$event = \Civi\Api4\Event::get(FALSE)
				->addWhere('id', '=', $mod_atts['eventid'])
                ->addWhere('start_date', '>', $today)
				->execute()
                ->first();
        
        if ( empty($event) ) {
            return '';
        }

		$event = \Civi\Api4\Event::get(FALSE)
				->addWhere('id', '=', $mod_atts['eventid'])
				->execute();
		
		// Respect the "Allow self-service cancellation or transfer?" event setting
		if ( !$event[0]['allow_selfcancelxfer'] ) {
			return '';
		}

		$confirmation_dialog = '<dialog id="event-cancellation-confirm-dialog-' . $mod_atts['eventid'] . '" class="event-cancellation-confirm-dialog">
                                    <div class="modal-content">
										<p><span class="event-title">' . $event[0]['title'] . '</span></p>
                                        <p>Are you sure you want to cancel this event registration?</p>
										<div class="modal-buttons">
											<button class="confirm-yes">Yes</button>
                                        	<button class="confirm-no">No</button>
										</div>
                                    </div>
                                </dialog>';

		return '<div class="event-links"><button data-eventId="' . $mod_atts['eventid'] . '" class="event-cancel-registration wp-civicrm-ux-button ' . $mod_atts['css_classes'] . '"><i class="' . $mod_atts['icon_classes'] . '"></i>' . $mod_atts['text'] . '</button>' . $confirmation_dialog . '</div>';
	}
}
