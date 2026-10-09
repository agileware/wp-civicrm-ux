<?php

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use \Sabre\VObject;

/**
 * Calls cancel_event_registration FormProcessor to perform the cancellation.
 */
class Civicrm_Ux_REST_Event_Cancel_Registration extends Abstract_Civicrm_Ux_REST {

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
		return 'cancel-event-registration/(?P<eid>[\d]+)';
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
            $event_id = absint($data['eid']);

            // The Form Processor binds to the logged-in contact, but it does not re-check the
            // conditions the button enforces before rendering, so repeat them here: the caller must
            // have an active registration on this event, the event must not have started, and it
            // must allow self-service cancellation. Without these an authenticated caller could
            // cancel after the deadline, on events that forbid it, or create a stray Cancelled
            // participant row for an event they were never on.
            $contact_id = CRM_Core_Session::singleton()->getLoggedInContactID();

            if ( empty( $contact_id ) ) {
                return new WP_Error( 'rest_forbidden', __( 'You do not have permission to cancel this event registration.', 'civicrm-ux' ), [ 'status' => 403 ] );
            }

            $participant = \Civi\Api4\Participant::get(FALSE)
                ->addJoin('ParticipantStatusType AS participant_status_type', 'LEFT')
                ->addWhere('event_id', '=', $event_id)
                ->addWhere('contact_id', '=', $contact_id)
                ->addWhere('participant_status_type.class', '!=', 'Negative')
                ->execute()
                ->first();

            if ( empty( $participant ) ) {
                return new WP_Error( 'rest_forbidden', __( 'You do not have an active registration for this event.', 'civicrm-ux' ), [ 'status' => 403 ] );
            }

            $event = \Civi\Api4\Event::get(FALSE)
                ->addSelect('id', 'allow_selfcancelxfer')
                ->addWhere('id', '=', $event_id)
                ->addWhere('is_active', '=', TRUE)
                ->addWhere('start_date', '>', current_time('mysql'))
                ->execute()
                ->first();

            if ( empty( $event ) || empty( $event['allow_selfcancelxfer'] ) ) {
                return new WP_Error( 'rest_forbidden', __( 'This event cannot be cancelled online.', 'civicrm-ux' ), [ 'status' => 403 ] );
            }

            $result = civicrm_api3('FormProcessor', 'cancel_event_registration', [
                'eid' => $event_id,
                'check_permissions' => true,
            ]);
        
            $response = new WP_REST_Response();
            $response->set_data( $result );
            $response->set_status( 200 );
        } catch ( CiviCRM_API3_Exception $e ) {
            $errorMessage = $e->getMessage();
            \CRM_Core_Error::debug_var( 'rest_cancel_event_registration', $errorMessage );
            return new WP_Error( 'rest_cancel_event_registration', 'CiviCRM API error.', ['status' => 500] );
        }

        return $response;
	}

	/**
	 * Check permissions to cancel event registration
	 * Verifies user is logged in and has permission to register for events
	 *
	 * @return bool
	 */
	public function check_permissions() {
		if ( ! current_user_can( 'register_for_events' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to cancel this event registration.', 'civicrm-ux' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}
}