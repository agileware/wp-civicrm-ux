<?php

use Civi\Api4\Event;

/**
 * Outputs the button to mark attendance to an event. Also outputs the corresponding modal dialog to select attendance.
 * To be used in listings.
 */
class Civicrm_Ux_Shortcode_Event_MarkAttendance_Button extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_markattendance_button';
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
            'text' => 'Mark Attendance',
            'eventid' => null,
            'attended_status' => 2, // Attended
            'not_attended_status' => 3, // No-show
			'css_classes' => '',
			'icon_classes' => 'fa fa-check',
        ], $atts, $tag);

		$args = [];

		$cid = CRM_Core_Session::singleton()->getLoggedInContactID();

		// Make sure the current logged in user has permission to edit their registration.
		// This should match up to the permission set in the mark_event_attendance Form Processor,
		// OR default to the 'register for events' permission
		$formProcessorInstance = \Civi\Api4\FormProcessorInstance::get(FALSE)
			->addSelect('permission')
			->addWhere('name', '=', 'mark_event_attendance')
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
        
		// Ensure the button is only rendered for the current logged in user, 
        // when they have an active registration - i.e. Registered status
		$participant = \Civi\Api4\Participant::get(FALSE)
				->addWhere('event_id', '=', $mod_atts['eventid'])
				->addWhere('contact_id', '=', $cid)
				->addWhere('status_id', '=', 1) // registered
				->execute()
                ->first();

		if ( empty($participant) ) {
			return '';
		}

        // Only display this button for events that have already passed.
        $today = current_time('mysql');
		$event = \Civi\Api4\Event::get(FALSE)
				->addWhere('id', '=', $mod_atts['eventid'])
                ->addWhere('end_date', '<=', $today)
				->execute()
                ->first();
        
        if ( empty($event) ) {
            return '';
        }

		// Buffer the output

		$confirmation_dialog = '<dialog id="event-markattendance-confirm-dialog-' . $mod_atts['eventid'] . '" class="event-markattendance-confirm-dialog">
                                    <div class="modal-content">';

		// Buffer the output
		ob_start();
			
		// Display an error message
		civicrm_ux_load_template_part( 'shortcode', 'event-markattendance-form', array_merge( $args, $mod_atts, ['contact_id' => $cid, 'participant' => $participant, 'event' => $event] ));

		$confirmation_dialog .= ob_get_clean();

		$confirmation_dialog .= '
                                        <button class="close"><span class="dashicons dashicons-no"></span></button>
                                    </div>
                                </dialog>';

		return '<div class="event-links"><button data-eventId="' . $mod_atts['eventid'] . '" data-participantId="' . $participant['id'] . '" class="event-mark-attendance wp-civicrm-ux-button ' . $mod_atts['css_classes'] . '"><i class="' . $mod_atts['icon_classes'] . '"></i>' . $mod_atts['text'] . '</button>' . $confirmation_dialog . '</div>';
	}
}
