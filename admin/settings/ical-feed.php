<?php

/**
 * 
 * Defines settings.
 * 
 * Auto-loaded from civicrm-ux-settings.php.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

namespace CiviCRM_UX\Settings\iCal;

use Civicrm_Ux_Shortcode_Event_ICal_Feed;
use CiviCRM_UX\SettingsTabs;

const SLUG = 'ical';
const LABEL = 'iCal Feed';
const GROUP  = 'civicrm_ux_options_group_ical';
const PAGE   = 'civicrm-ux-settings-ical'; // A tab on the page
const OPTION_NAME = Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION;

SettingsTabs::register( SLUG, LABEL, 40 );

register_setting( GROUP, OPTION_NAME );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_ical_feed',
    'iCal Feed',
    __NAMESPACE__ . '\ical_feed_cb',
    PAGE
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
    PAGE,
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
    $option_name = OPTION_NAME;

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