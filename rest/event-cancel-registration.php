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
		if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
			return false;
		}

		// Verify user has CiviCRM permission to register for events
		civicrm_initialize();
		return CRM_Core_Permission::check( 'register for events' );
	}
}