<?php

/**
 * 
 * Defines settings.
 * 
 * Auto-loaded from civicrm-ux-settings.php.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

namespace CiviCRM_UX\Settings\Contributions;

use CiviCRM_UX\SettingsTabs;

const SLUG = 'contributions';
const LABEL = 'Contributions';
const GROUP  = 'civicrm_ux_options_group_contributions';
const PAGE   = 'civicrm-ux-settings-contributions'; // A tab on the page
const OPTION_NAME = 'civicrm_contribution_ux';

SettingsTabs::register( SLUG, LABEL, 20 );

register_setting( GROUP, OPTION_NAME );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_contributions',
    'Contributions',
    __NAMESPACE__ . '\info_cb',
    PAGE
);

add_settings_section(
    'civicrm_ux_settings_section_contribution_page_tweaks',
    '', // Leave title blank to suppress <h2>
    __NAMESPACE__ . '\contribution_page_tweaks_cb',
    PAGE
);



/**
 * 
 * ADD FIELDS
 * 
 */
// Build the array of settings fields
$fields = [
    'is_recur_default' => [ 
        'title'                 => 'Recur payments By Default', 
        'section'               => 'civicrm_ux_settings_section_contribution_page_tweaks', 
        'default_value'         => 0,
        'render_callback'       => __NAMESPACE__ . '\checkbox_cb',
        'help_text_callback'    => null,
        'field_options'         => [
            'label' => 'For all Contribution Pages, set the payment to recurring by default.'
        ],
    ],
    'is_autorenew_default' => [ 
        'title'                 => 'Auto-renew Memberships By Default', 
        'section'               => 'civicrm_ux_settings_section_contribution_page_tweaks', 
        'default_value'         => 0,
        'render_callback'       => __NAMESPACE__ . '\checkbox_cb',
        'help_text_callback'    => null,
        'field_options'         => [
            'label' => 'For all Membership Contribution Pages, set the membership to auto-renew by default.'
        ],
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
        [ 
            'key'           => $key, 
            'default_value' => $field['default_value'],
            'help'          => $field['help_text_callback'], 
            'label'         => isset($field['field_options']['label']) ? $field['field_options']['label'] : '',
        ]
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

function contribution_page_tweaks_cb() { 
    echo '<h3>Contribution Page Tweaks</h3>';
}

function checkbox_cb( $args ) {
    $option_name = OPTION_NAME;

    $key = $args['key'];
    $options = get_option( $option_name, [] );
    $raw_value = $options[ $key ] ?? 0;

    // Normalize to boolean: treat "on", "1", 1, true as checked
    $is_checked = in_array( $raw_value, [ 'on', '1', 1, true ], true );

    $checked = checked( $is_checked, true, false );
    $label = $args['label'] ?? '';

    printf( '<input type="checkbox" id="%1$s" name="%1$s" value=1 %2$s><label for="%1$s">%3$s</label>',
        "{$option_name}[$key]",
        $checked,
        $label
    );
}