<?php

/**
 * 
 * Defines settings.
 * 
 * Auto-loaded from civicrm-ux-settings.php.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

// WPCIVIUX-148 settings

namespace CiviCRM_UX\Settings\PluginActivationBlocks;

use CiviCRM_UX\SettingsTabs;

const SLUG = 'plugin-activation-blocks';
const LABEL = 'Plugin Activation Blocks';
const GROUP  = 'civicrm_ux_options_group_plugin-activation-blocks';
const PAGE   = 'civicrm-ux-settings-plugin-activation-blocks'; // A tab on the page
const OPTION_NAME = 'civicrm_plugin_activation_blocks';

SettingsTabs::register( SLUG, LABEL, 40 );

register_setting( GROUP, 'civicrm_plugin_activation_blocks' );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_plugin_activation_blocks',
    'Plugin Activation Blocks',
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
    'event_tickets' => [ 
        'title'                 => 'Event Tickets', 
        'section'               => 'civicrm_ux_settings_section_plugin_activation_blocks', 
        'render_callback'       => __NAMESPACE__ . '\checkbox_cb',
        'help_text_callback'    => null,
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
        [ 'key' => $key, 'help' => $field['help_text_callback'] ]
    );
}



/**
 * 
 * CALLBACKS
 * 
 */
function info_cb() {
    printf( '<p>Prevent these plugins from being activated.</p>' );
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

    printf( '<input type="checkbox" id="%1$s" name="civicrm_plugin_activation_blocks[%2$s]" value=1 %3$s><label for="%1$s">%4$s</label>',
        $key,
        "{$option_name}[$key]",
        $checked,
        $label
    );
}