<?php

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
            $participant_id = absint($data['pid']);
            $event_id = absint($data['eid']);
            $attendance = absint($data['attendance']) == 1 ? absint($data['a_stat']) : absint($data['na_stat']);
            
            $result = civicrm_api3('FormProcessor', 'mark_event_attendance', [
                'pid' => $participant_id,
                'eid' => $event_id,
                'attendance' => $attendance,
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
}