<?php

use Civi\Api4\Event;

/**
 * Outputs the button to complete evaluation for an event.
 * To be used in listings.
 */
class Civicrm_Ux_Shortcode_Event_CompleteEvaluation_Button extends Abstract_Civicrm_Ux_Shortcode {
	/**
	 * @return string The name of shortcode
	 */
	public function get_shortcode_name() {
		return 'ux_event_completeevaluation_button';
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
            'text' => 'Complete Evaluation',
            'event_id' => null,
            'participant_status' => null,
            'url' => null,
            'fallback_url' => null,
            'css_classes' => '',
            'icon_classes' => 'fa fa-pencil-square-o',
        ], $atts, $tag);

		$cid = CRM_Core_Session::singleton()->getLoggedInContactID();

        // Only display this button for events that have already passed.
        $today = current_time('mysql');
		$event = \Civi\Api4\Event::get(FALSE)
				->addWhere('id', '=', $mod_atts['event_id'])
                ->addWhere('end_date', '<=', $today)
				->execute()
                ->first();
        
        if ( empty($event) ) {
            return '';
        }

        // Ensure the button is only rendered for the current logged in user, 
        // when they have the given participant_status for the event
		$participant = \Civi\Api4\Participant::get(FALSE)
                ->addWhere('event_id', '=', $mod_atts['event_id'])
                ->addWhere('contact_id', '=', $cid)
                ->addWhere('status_id', '=', $mod_atts['participant_status'])
                ->execute()
                ->first();

        if ( empty($participant) ) {
            return '';
        }

        $mod_atts['url'] = !empty($mod_atts['url']) ? $mod_atts['url'] : $mod_atts['fallback_url'];

        // if the url is still empty, don't output the button
        if ( empty($mod_atts['url']) ) {
            return '';
        } 

        // Build the URL parameters
        $query = [
            'pid' => $participant['id'],
            'eid' => $event['id'],
            'event_title' => $event['title']
        ];

        // Append the URL parameters
        $mod_atts['url'] = add_query_arg($query, $mod_atts['url']);

		return '<a href="' . $mod_atts['url'] . '" class="button wp-civicrm-ux-button event-complete-evaluation ' . $mod_atts['css_classes'] . '"><i class="' . $mod_atts['icon_classes'] . '"></i>' . $mod_atts['text'] . '</a>';
	}
}
