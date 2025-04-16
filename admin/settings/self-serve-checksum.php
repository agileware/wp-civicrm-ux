<?php

namespace CiviCRM_UX\Settings\SSC;

$group  = 'civicrm_ux_options_group_ssc';
$page   = 'civicrm-ux-settings-new-ssc'; // A tab on the page

function get_option_name() {
    return 'self_serve_checksum';
}

// WPCIVIUX-162 settings
register_setting( $group, get_option_name(), array(
    'type' => 'array',
    'sanitize_callback' => __NAMESPACE__ . '\sanitize_self_serve_checksum',
) );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_self_serve_checksum',
    'Self Serve Checksum',
    __NAMESPACE__ . '\info_cb',
    $page
);

add_settings_section(
    'civicrm_ux_settings_section_ssc_protection_form',
    'Self Serve Checksum',
    __NAMESPACE__ . '\ssc_protection_form_cb',
    $page
);

add_settings_section(
    'civicrm_ux_settings_section_ssc_email',
    'Self Serve Checksum Email',
    __NAMESPACE__ . '\ssc_email_cb',
    $page
);



/**
 * 
 * ADD FIELDS
 * 
 */
// Build the array of settings fields
$fields = [
    'form_text' => [ 
        'title' => 'Form Text', 
        'section' => 'civicrm_ux_settings_section_ssc_protection_form', 
        'render_callback' => __NAMESPACE__ . '\ssc__textarea_cb',
        'help_text_callback' => __NAMESPACE__ . '\ssc__form_text_help_text_cb',
        'editor_options' => [ ], 
    ],
    'form_confirmation_text' => [ 
        'title' => 'Confirmation Text', 
        'section' => 'civicrm_ux_settings_section_ssc_protection_form', 
        'render_callback' => __NAMESPACE__ . '\ssc__textarea_cb',
        'help_text_callback' => __NAMESPACE__ . '\ssc__form_confirmation_text_help_text_cb',
        'editor_options' => [ ],
    ],
    'form_invalid_contact_text' => [ 
        'title' => 'Invalid Contact Text', 
        'section' => 'civicrm_ux_settings_section_ssc_protection_form', 
        'render_callback' => __NAMESPACE__ . '\ssc__textarea_cb',
        'help_text_callback' => __NAMESPACE__ . '\ssc__form_invalid_contact_text_help_text_cb',
        'editor_options' => [ ], 
    ],
    'email_message' => [ 
        'title' => 'Email Message', 
        'section' => 'civicrm_ux_settings_section_ssc_email', 
        'render_callback' => __NAMESPACE__ . '\ssc__textarea_cb', 
        'help_text_callback' => __NAMESPACE__ . '\ssc__email_message_help_text_cb',
        'editor_options' => [ 
            'media_buttons' => true,   // Show media upload buttons
            'textarea_rows' => 10,
        ], 
    ],
];

// Add the fields
$option_name = get_option_name();
foreach ( $fields as $key => $field ) {
    add_settings_field(
        "{$option_name}_{$key}",
        $field['title'],
        $field['render_callback'],
        $page,
        $field['section'],
        [ 'key' => $key, 'options' => $field['editor_options'], 'help' => $field['help_text_callback'] ]
    );
}



/**
 * 
 * CALLBACKS
 * 
 */
function info_cb() {
    printf ( '<p>The <code>[ux_self_serve_checksum]</code> shortcode can be used to protect your forms and force a valid checksum before form submission.</p>' );
    printf ( '<p>Read more on how to use the shortcode in the <a href="%s" target="_blank">CiviCRM UX User Guide.</a></p>',
             esc_url('https://github.com/agileware/wp-civicrm-ux/blob/GFCV-107/USAGE.md#self-serve-checksum-shortcode') );
    printf ( '<p>Configure the contents of the Self Serve Checksum request form and the email to be sent to the user below.</p>' );
}

function ssc_protection_form_cb() {
    printf ( '<p>Configure the messings around the Self Serve Checksum Protection form on the front end.</p>' );
}

function ssc_email_cb() {
    printf ( '<p>This email will be sent to users to complete their authentication via the Self Serve Checksum Protection Form, and grant them access to the protected page.</p>' );
}

function ssc__textarea_cb( $args ) {
    $option_name = get_option_name();

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $value = isset( $options[ $key ] ) ? $options[ $key ] : '';

    $default_editor_options = [
        'media_buttons' => false,
        'textarea_rows' => 5,
        'teeny' => false,
        'quicktags' => true
    ];
    $override_editor_options = $args['options'] ?? [];

    // Merge defaults with overrides
    $editor_options = array_merge(
        $default_editor_options,
        $override_editor_options,
        [ 'textarea_name' => "{$option_name}[$key]" ] // Always set this explicitly
    );

    echo wp_editor(
        $value,      // Content of the editor
        "{$option_name}_{$key}",      // Unique ID for the editor
        $editor_options
    );

    // Invoke help callback if provided
    if ( isset( $args['help'] ) && is_callable( $args['help'] ) ) {
        echo '<div class="description">';
        call_user_func( $args['help'], $key );
        echo '</div>';
    }
}

function ssc__form_text_help_text_cb() {
    printf( '<p>Appears above the Self Serve Checksum protection form.</p>' );
}

function ssc__form_confirmation_text_help_text_cb() {
    printf( '<p>Informs the user that their request to view the protected form has been confirmed. An email will be sent to them with the link.</p>
            <p>The following tokens are available to help you build your content:</p>
            <ul>
                <li><code>{email_address}</code> - Outputs the email address the user provided.</li>
                <li><code>{page_title}</code> - Outputs the title of the form page the email was requested from.</li>
            </ul>' );
}

function ssc__form_invalid_contact_text_help_text_cb() {
    printf( '<p>Informs the user that their email address is not currently in your CiviCRM records. No email will be sent to them.</p>
            <p>The following tokens are available to help you build your content:</p>
            <ul>
                <li><code>{email_address}</code> - Outputs the email address the user provided.</li>
                <li><code>{page_title}</code> - Outputs the title of the form page the email was requested from.</li>
            </ul>' );
}

function ssc__email_message_help_text_cb() {
    printf( '<p>Use the following tokens to build your email message to the user:</p>
            <ul>
                <li><code>{checksum_url}</code> - <strong>Required.</strong> Outputs the unique URL for the contact to complete the form.</li>
                <li><code>{page_title}</code> - Outputs the title of the form page the email was requested from.</li>
            </ul>' );
}



/**
 * 
 * CUSTOM SANITIZERS
 * 
 */
function sanitize_self_serve_checksum( $input ) {
    // Custom sanitize callback to handle HTML content
    $option_name = get_option_name();
    $sanitized = [];
    foreach ($input as $field => $value) {
        switch ($field) {
            case 'form_text':
            case 'form_confirmation_text':
            case 'form_invalid_contact_text':
                // Use wp_kses_post to allow only safe HTML for WYSIWYG content
                $sanitized[$field] = wp_kses_post($value);
                break;
            case 'email_message':
                // Validate that the {checksum_url} custom token is present in the content
                if (strpos($value, '{checksum_url}') === false) {
                    // Add a settings error if the token is missing
                    add_settings_error(
                        $option_name, // Slug title of the setting
                        'missing_checksum_token', // Error code
                        'The Self Serve Checksum email message must include the {checksum_url} token to be valid.', // Error message
                        'error' // Type of the message
                    );

                    // Prevent saving by returning false
                    return false;
                }
                // Use wp_kses_post to allow only safe HTML for WYSIWYG content
                $sanitized[$field] = wp_kses_post($value);
                break;
            default:
                $sanitized[$field] = sanitize_text_field($value);
                break;
        }
    }

    return $sanitized;
}