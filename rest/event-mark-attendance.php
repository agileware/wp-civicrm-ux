<?php

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use \Sabre\VObject;

/**
 * Calls mark_event_attendance FormProcessor to perform the Participant Status change.
 */
class Civicrm_Ux_REST_Event_Mark_Attendance extends Abstract_Civicrm_Ux_REST {

	/**
	 * @return string
	 */
	public function get_route() {
		return 'civicrm_ux';
	}

	/**
	 * @return string
	 */
	public function get_endpoint() {
		return 'mark-event-attendance/(?P<pid>[\d]+)/(?P<eid>[\d]+)/(?P<attendance>[\d]+)/(?P<a_stat>[\d]+)/(?P<na_stat>[\d]+)';
	}

	/**
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * @param \WP_REST_Request $data
	 *
	 * @return mixed
	 * @throws CiviCRM_API3_Exception
	 */
	public function rest_api_callback( $data ) {
		civicrm_initialize();

        try {
            $participant_id  = absint($data['pid']);
            $event_id        = absint($data['eid']);
            $attendance_flag = absint($data['attendance']);

            // The status ids arrive in the URL and the Form Processor writes the chosen one as the
            // participant status with permission checks off, so every value below is validated
            // server-side rather than trusted: the flag must be 0 or 1, the caller must own a
            // *Registered* participant on a past event (the same preconditions the button enforces
            // before it renders), and the target status must be a real attendance outcome - not a
            // Pending/Waiting status that would let a caller bypass payment, approval or capacity.
            if ( $attendance_flag !== 0 && $attendance_flag !== 1 ) {
                return new WP_Error( 'rest_forbidden', __( 'Invalid attendance value.', 'civicrm-ux' ), [ 'status' => 400 ] );
            }
            $target_status = $attendance_flag === 1 ? absint($data['a_stat']) : absint($data['na_stat']);

            $contact_id = CRM_Core_Session::singleton()->getLoggedInContactID();

            if ( empty( $contact_id ) ) {
                return new WP_Error(
                    'rest_forbidden',
                    __( 'You do not have permission to mark attendance for this event registration.', 'civicrm-ux' ),
                    [ 'status' => 403 ]
                );
            }

            // The participant must belong to the caller, be on this event, and currently be
            // Registered (status 1) - so this endpoint can only move a confirmed registration to
            // an attendance outcome, never promote a pending/waitlisted/unapproved one.
            $participant = \Civi\Api4\Participant::get(FALSE)
                ->addWhere('id', '=', $participant_id)
                ->addWhere('event_id', '=', $event_id)
                ->addWhere('contact_id', '=', $contact_id)
                ->addWhere('status_id', '=', 1)
                ->execute()
                ->first();

            if ( empty( $participant ) ) {
                return new WP_Error(
                    'rest_forbidden',
                    __( 'You do not have permission to mark attendance for this event registration.', 'civicrm-ux' ),
                    [ 'status' => 403 ]
                );
            }

            // Attendance only makes sense once the event has ended.
            $event = \Civi\Api4\Event::get(FALSE)
                ->addWhere('id', '=', $event_id)
                ->addWhere('end_date', '<=', current_time('mysql'))
                ->execute()
                ->first();

            if ( empty( $event ) ) {
                return new WP_Error(
                    'rest_forbidden',
                    __( 'Attendance can only be marked for an event that has ended.', 'civicrm-ux' ),
                    [ 'status' => 403 ]
                );
            }

            // The target status must be an active attendance outcome (Positive/Negative class),
            // which excludes Pending and Waiting statuses.
            $status = \Civi\Api4\ParticipantStatusType::get(FALSE)
                ->addWhere('id', '=', $target_status)
                ->addWhere('is_active', '=', TRUE)
                ->addWhere('class', 'IN', ['Positive', 'Negative'])
                ->execute()
                ->first();

            if ( empty( $status ) ) {
                return new WP_Error(
                    'rest_forbidden',
                    __( 'Invalid attendance status.', 'civicrm-ux' ),
                    [ 'status' => 403 ]
                );
            }

            $result = civicrm_api3('FormProcessor', 'mark_event_attendance', [
                'pid' => $participant_id,
                'eid' => $event_id,
                'attendance' => $target_status,
                'check_permissions' => false,
            ]);
        
            $response = new WP_REST_Response();
            $response->set_data( $result );
            $response->set_status( 200 );
        } catch ( CiviCRM_API3_Exception $e ) {
            $errorMessage = $e->getMessage();
            \CRM_Core_Error::debug_var( 'rest_mark_event_attendance', $errorMessage );
            return new WP_Error( 'rest_mark_event_attendance', 'CiviCRM API error.', ['status' => 500] );
        }

        return $response;
	}

	/**
	 * Check permissions to mark event attendance
	 * Verifies user is logged in and has permission to register for events
	 *
	 * @return bool
	 */
	public function check_permissions() {
		if ( ! is_user_logged_in() || ! current_user_can( 'register_for_events' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to mark attendance for this event registration.', 'civicrm-ux' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}