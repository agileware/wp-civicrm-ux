<?php

/**
 * 
 * Defines settings.
 * 
 * Auto-loaded from civicrm-ux-settings.php.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

namespace CiviCRM_UX\Settings\Memberships;

use CiviCRM_UX\SettingsTabs;

const SLUG = 'memberships';
const LABEL = 'Memberships';
const GROUP  = 'civicrm_ux_options_group_memberships';
const PAGE   = 'civicrm-ux-settings-memberships'; // A tab on the page
const OPTION_NAME = 'civicrm_summary_options'; // Backwards compatibility

SettingsTabs::register( SLUG, LABEL, 10 );

register_setting( GROUP, OPTION_NAME );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_memberships',
    'Memberships',
    __NAMESPACE__ . '\info_cb',
    PAGE
);

add_settings_section(
    'civicrm_ux_settings_section_membership_summary',
    '', // Leave title blank to suppress <h2>
    __NAMESPACE__ . '\membership_summary_cb',
    PAGE
);



/**
 * 
 * ADD FIELDS
 * 
 */
// Build the array of settings fields
$fields = [
    'civicrm_summary_show_renewal_date' => [ 
        'title'                 => 'Renewal Date', 
        'section'               => 'civicrm_ux_settings_section_membership_summary', 
        'default_value'         => '30',
        'render_callback'       => __NAMESPACE__ . '\show_renewal_date_cb',
        'help_text_callback'    => null,
    ],
    'civicrm_summary_membership_join_URL' => [ 
        'title'                 => 'Membership Join Page', 
        'section'               => 'civicrm_ux_settings_section_membership_summary', 
        'default_value'         => '/join/',
        'render_callback'       => __NAMESPACE__ . '\text_cb',
        'help_text_callback'    => __NAMESPACE__ . '\membership_join_url_help_text_cb',
    ],
    'civicrm_summary_membership_renew_URL' => [ 
        'title'                 => 'Membership Renewal Page', 
        'section'               => 'civicrm_ux_settings_section_membership_summary', 
        'default_value'         => '/renew/',
        'render_callback'       => __NAMESPACE__ . '\text_cb',
        'help_text_callback'    => __NAMESPACE__ . '\membership_renew_url_help_text_cb',
    ],
];

// Add the fields
$option_name = OPTION_NAME;
foreach ($fields as $key => $field) {
    add_settings_field(
        "{$option_name}_{$key}",
        $field['title'],
        $field['render_callback'],
        PAGE,
        $field['section'],
        [ 'key' => $key, 'default_value' => $field['default_value'], 'help' => $field['help_text_callback'] ],
    );
}



/**
 * 
 * CALLBACKS
 * 
 */
function info_cb() {
    echo '<p><strong>TODO:</strong> Add documentation for what this is for and what it does.</p>';
}

function membership_summary_cb() { 
    echo '<h3>Membership Summary</h3>';
}

function show_renewal_date_cb( $args ) {
    $option_name = OPTION_NAME;

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $default_value = $args['default_value'] ?? '';
    $value = $options[ $key ] ?? $default_value;

    printf( '<input 
                type="number" min="0" step="1"
                id="%1$s"
                name="%2$s"
                value="%3$d"/>',
            $key,
            "{$option_name}[$key]",
            $value
    );
    echo '<p class="description">The number of days to show the renewal notice and URL.</p>';
}

function text_cb( $args ) {
    $option_name = OPTION_NAME;

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $default_value = $args['default_value'] ?? '';
    $value = $options[ $key ] ?? $default_value;

    printf( '<input 
                type="text"
                id="%1$s"
                name="%2$s"
                value="%3$s"/>',
            $key,
            "{$option_name}[$key]",
            $value
    );

    // Invoke help callback if provided
    if ( isset( $args['help'] ) && is_callable( $args['help'] ) ) {
        echo '<div class="description">';
        call_user_func( $args['help'], $key );
        echo '</div>';
    }
}

function membership_join_url_help_text_cb() {
    echo '<p class="description">URL for the Membership Join page.</p>';
}

function membership_renew_url_help_text_cb() {
    echo '<p class="description">URL for the Membership Join page.</p>';
}