<?php

namespace CiviCRM_UX\Settings\iCal;

use Civicrm_Ux_Shortcode_Event_ICal_Feed;

$group  = 'civicrm_ux_options_group_ical';
$page   = 'civicrm-ux-settings-new-ical'; // A tab on the page

function get_option_name() {
    return Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION;
}

register_setting( $group, get_option_name() );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_ical_feed',
    'iCal Feed',
    __NAMESPACE__ . '\ical_feed_cb',
    $page
);



/**
 * 
 * ADD FIELDS
 * 
 */
add_settings_field(
    'ical-hash-field', // compatibility with old implementation
    'Random Hash',
    __NAMESPACE__ . '\ical_feed__hash_cb',
    $page,
    'civicrm_ux_settings_section_ical_feed'
);



/**
 * 
 * CALLBACKS
 * 
 */
function ical_feed_cb() {
    printf( '<p><strong>TODO:</strong> Documentation for what this is used for, and where to use it. Something about the iCal Feed shortcode.</p>' );
}

function ical_feed__hash_cb() {
    $option_name = get_option_name();

    // Build the URL
    $url_param = [ 'hash' => get_option(Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION) ];
    $url = add_query_arg( $url_param, get_rest_url( NULL, '/' . Civicrm_Ux_Shortcode_Event_ICal_Feed::API_NAMESPACE . '/' . Civicrm_Ux_Shortcode_Event_ICal_Feed::INTERNAL_ENDPOINT) );
        
    printf( '<input 
                id="ical-hash-field" 
                type="text"
                name="%s" 
                size="40"
                value="%s" />',
             $option_name,
             esc_attr( get_option( $option_name ) )
    );
    printf( '<button id="generate-ical-hash" type="button">Re-generate Hash</button>' );
    printf( '<p>The internal feed url is: <a id="ical-internal-url" href="%1$s">%1$s</a></p>',
             $url );
}