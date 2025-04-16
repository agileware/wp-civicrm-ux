<?php

namespace CiviCRM_UX\Settings\CloudflareTurnstile;

$group  = 'civicrm_ux_options_group';
$page   = 'civicrm-ux-settings-new-cf-turnstile'; // A tab on the page

function get_option_name() {
    return 'civicrm_ux_cf_turnstile';
}

// WPCIVIUX-167 settings
register_setting( 'civicrm_ux_options_group', get_option_name(), array(
    'type' => 'array',
    'sanitize_callback' => __NAMESPACE__ . '\sanitize_cf_turnstile',
) );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_cloudflare_turnstile',
    'Cloudflare Turnstile',
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
    'sitekey' => [ 
        'title' => 'Sitekey', 
        'section' => 'civicrm_ux_settings_section_cloudflare_turnstile', 
        'render_callback' => __NAMESPACE__ . '\text_cb',
        'help_text_callback' => null,
        'field_options' => [ ],
    ],
    'secret_key' => [ 
        'title' => 'Secret Key', 
        'section' => 'civicrm_ux_settings_section_cloudflare_turnstile', 
        'render_callback' => __NAMESPACE__ . '\text_cb',
        'help_text_callback' => null,
        'field_options' => [ ],
    ],
    'theme' => [ 
        'title' => 'Theme', 
        'section' => 'civicrm_ux_settings_section_cloudflare_turnstile', 
        'render_callback' => __NAMESPACE__ . '\select_cb', 
        'help_text_callback' => null,
        'field_options' => [
            'choices' => [
                'auto' => 'Auto',
                'light' => 'Light',
                'dark' => 'dark',
            ]
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
        [ 'key' => $key, 'help' => $field['help_text_callback'], 'field_options' => $field['field_options'] ]
    );
}



/**
 * 
 * CALLBACKS
 * 
 */
function info_cb() {
    printf ( '<p>Protect your Self Serve Checksum Form against spam using Cloudflare Turnstile.</p>' );
    printf ( '<p><a href="%s" target="_blank">Learn more about this free service.</a></p>',
             esc_url('https://www.cloudflare.com/en-gb/products/turnstile/') );
    printf ( '<p>Create a widget and get your sitekey and secret key from <a href="%1$s" target="_blank">%1$s</a></p>',
             esc_url('https://dash.cloudflare.com/?to=/:account/turnstile')
    );            
}

function text_cb( $args ) {
    $option_name = get_option_name();

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $value = isset( $options[ $key ] ) ? $options[ $key ] : '';

    printf( '<input 
                type="text"
                size="50"
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

function select_cb( $args ) {
    $option_name = get_option_name();

    $choices = isset( $args['field_options']['choices'] ) ? $args['field_options']['choices'] : false;
    if ( !$choices ) {
        return; // Output nothing
    }

    $key = $args['key'];
    $options = get_option( $option_name, [] );

    $choices_html = '';
    foreach ( $choices as $choice_key => $label ) {
        $raw_value = $options[ $key ] ?? 0;
        $selected = selected( $raw_value, $choice_key, false );

        $choices_html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>',
                $choice_key,
                $selected,
                $label );
    }

    printf( '<select name="%1$s" id="theme_select">%2$s</select>',
            "{$option_name}[$key]",
            $choices_html
    );

    // Invoke help callback if provided
    if ( isset( $args['help'] ) && is_callable( $args['help'] ) ) {
        echo '<div class="description">';
        call_user_func( $args['help'], $key );
        echo '</div>';
    }
}



/**
 * 
 * CUSTOM SANITIZERS
 * 
 */
function sanitize_cf_turnstile( $input ) {
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
                        'self_serve_checksum', // Slug title of the setting
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