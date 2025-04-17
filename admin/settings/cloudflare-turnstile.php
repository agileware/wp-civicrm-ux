<?php

/**
 * 
 * Defines settings.
 * 
 * Auto-loaded from civicrm-ux-settings.php.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

// WPCIVIUX-167 settings

namespace CiviCRM_UX\Settings\CloudflareTurnstile;

use CiviCRM_UX\SettingsTabs;

const SLUG = 'cf-turnstile';
const LABEL = 'Cloudflare Turnstile';
const GROUP  = 'civicrm_ux_options_group_cf-turnstile';
const PAGE   = 'civicrm-ux-settings-cf-turnstile'; // A tab on the page
const OPTION_NAME = 'civicrm_ux_cf_turnstile';

SettingsTabs::register( SLUG, LABEL, 40 );

// WPCIVIUX-167 settings
register_setting( GROUP, OPTION_NAME, array(
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
    PAGE
);



/**
 * 
 * ADD FIELDS
 * 
 */
// Build the array of settings fields
$fields = [
    'sitekey' => [ 
        'title'                 => 'Sitekey', 
        'section'               => 'civicrm_ux_settings_section_cloudflare_turnstile', 
        'default_value'         => '',
        'render_callback'       => __NAMESPACE__ . '\text_cb',
        'help_text_callback'    => null,
        'field_options'         => [ ],
    ],
    'secret_key' => [ 
        'title'                 => 'Secret Key', 
        'section'               => 'civicrm_ux_settings_section_cloudflare_turnstile', 
        'default_value'         => '',
        'render_callback'       => __NAMESPACE__ . '\text_cb',
        'help_text_callback'    => null,
        'field_options'         => [ ],
    ],
    'theme' => [ 
        'title'                 => 'Theme', 
        'section'               => 'civicrm_ux_settings_section_cloudflare_turnstile', 
        'default_value'         => 'auto',
        'render_callback'       => __NAMESPACE__ . '\select_cb', 
        'help_text_callback'    => null,
        'field_options'         => [
            'choices' => [
                'auto'  => 'Auto',
                'light' => 'Light',
                'dark'  => 'Dark',
            ]
        ],
    ],
];

// Add the fields
$option_name = OPTION_NAME;
foreach ( $fields as $key => $field ) {
    add_settings_field(
        "{$option_name}_{$key}",
        $field['title'],
        $field['render_callback'],
        PAGE,
        $field['section'],
        [ 
            'key'           => $key, 
            'default_value' => $field['default_value'],
            'help'          => $field['help_text_callback'], 
            'field_options' => $field['field_options'] 
        ],
    );
}



/**
 * 
 * CALLBACKS
 * 
 */
function info_cb() {
    echo '<p>Protect your Self Serve Checksum Form against spam using Cloudflare Turnstile.</p>';
    printf ( '<p><a href="%s" target="_blank">Learn more about this free service.</a></p>',
             esc_url('https://www.cloudflare.com/en-gb/products/turnstile/') );
    printf ( '<p>Create a widget and get your sitekey and secret key from <a href="%1$s" target="_blank">%1$s</a></p>',
             esc_url('https://dash.cloudflare.com/?to=/:account/turnstile')
    );            
}

function text_cb( $args ) {
    $option_name = OPTION_NAME;

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $default_value = $args['default_value'] ?? '';
    $value = $options[ $key ] ?? $default_value;

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
    $option_name = OPTION_NAME;

    $choices = isset( $args['field_options']['choices'] ) ? $args['field_options']['choices'] : false;
    if ( !$choices ) {
        return; // Output nothing
    }

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $default_value = $args['default_value'] ?? 0;
    $value = $options[ $key ] ?? $default_value;

    $choices_html = '';
    foreach ( $choices as $choice_key => $label ) {
        $selected = selected( $value, $choice_key, false );

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
    // Custom sanitize callback to ensure whitespace is trimmed
    $sanitized = [];
    foreach ($input as $field => $value) {
        if ( $field === 'sitekey' || $field === 'secret_key' ) {
            $sanitized[$field] = sanitize_text_field($value);
        }
    }

    return $sanitized;
}