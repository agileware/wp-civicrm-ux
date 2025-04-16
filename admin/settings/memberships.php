<?php

namespace CiviCRM_UX\Settings\Memberships;

$group  = 'civicrm_ux_options_group_memberships';
$page   = 'civicrm-ux-settings-new-memberships'; // A tab on the page

function get_option_name() {
    return 'civicrm_summary_options'; // Backwards compatibility
}

register_setting( $group, get_option_name() );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_memberships',
    'Memberships',
    __NAMESPACE__ . '\info_cb',
    $page
);



/**
 * 
 * ADD FIELDS
 * 
 */
// Build the array of settings fields
$fields = [
    'civicrm_summary_show_renewal_date' => [ 
        'title' => 'Renewal Date', 
        'section' => 'civicrm_ux_settings_section_memberships', 
        'render_callback' => __NAMESPACE__ . '\show_renewal_date_cb',
        'help_text_callback' => null,
    ],
    'civicrm_summary_membership_join_URL' => [ 
        'title' => 'Membership Join Page', 
        'section' => 'civicrm_ux_settings_section_memberships', 
        'render_callback' => __NAMESPACE__ . '\text_cb',
        'help_text_callback' => __NAMESPACE__ . '\membership_join_url_help_text_cb',
    ],
    'civicrm_summary_membership_renew_URL' => [ 
        'title' => 'Membership Renewal Page', 
        'section' => 'civicrm_ux_settings_section_memberships', 
        'render_callback' => __NAMESPACE__ . '\text_cb',
        'help_text_callback' => __NAMESPACE__ . '\membership_renew_url_help_text_cb',
    ],
];

// Add the fields
$option_name = get_option_name();
foreach ($fields as $key => $field) {
    add_settings_field(
        "{$option_name}_{$key}",
        $field['title'],
        $field['render_callback'],
        $page,
        $field['section'],
        [ 'key' => $key, 'help' => $field['help_text_callback'] ]
    );
}



/**
 * 
 * CALLBACKS
 * 
 */
function info_cb() {
    printf( '<p><strong>TODO:</strong> Add documentation for what this is for and what it does.</p>' );
}

function show_renewal_date_cb( $args ) {
    $option_name = get_option_name();

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $value = isset( $options[ $key ] ) ? $options[ $key ] : '';

    printf( '<input 
                type="number" min="0" step="1"
                id="%1$s"
                name="%2$s"
                value="%3$d"/>',
            $key,
            "{$option_name}[$key]",
            $value
    );
    printf( '<p class="description">The number of days to show the renewal notice and URL.</p>' );
}

function text_cb( $args ) {
    $option_name = get_option_name();

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $value = isset( $options[ $key ] ) ? $options[ $key ] : '';

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
    printf( '<p class="description">URL for the Membership Join page.</p>' );
}

function membership_renew_url_help_text_cb() {
    printf( '<p class="description">URL for the Membership Join page.</p>' );
}