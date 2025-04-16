<?php

namespace CiviCRM_UX\Settings\PluginActivationBlocks;

$group  = 'civicrm_ux_options_group_plugin-activation-blocks';
$page   = 'civicrm-ux-settings-plugin-activation-blocks'; // A tab on the page

// WPCIVIUX-148 settings
register_setting( $group, 'civicrm_plugin_activation_blocks' );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_plugin_activation_blocks',
    'Plugin Activation Blocks',
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
    'event_tickets' => [ 
        'title' => 'Event Tickets', 
        'section' => 'civicrm_ux_settings_section_plugin_activation_blocks', 
        'render_callback' => __NAMESPACE__ . '\checkbox_cb',
        'help_text_callback' => null,
    ],
];

// Add the fields
foreach ($fields as $key => $field) {
    add_settings_field(
        "civicrm_plugin_activation_blocks_$key",
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
    printf( '<p>Prevent these plugins from being activated.</p>' );
}

function checkbox_cb( $args ) {
    $key = $args['key'];
    $options = get_option( 'civicrm_plugin_activation_blocks', [] );
    $raw_value = $options[ $key ] ?? 0;

    // Normalize to boolean: treat "on", "1", 1, true as checked
    $is_checked = in_array( $raw_value, [ 'on', '1', 1, true ], true );

    $checked = checked( $is_checked, true, false );
    $label = $args['label'] ?? '';

    printf( '<input type="checkbox" id="%1$s" name="civicrm_plugin_activation_blocks[%1$s]" value=1 %2$s><label for="%1$s">%3$s</label>',
        $key,
        $checked,
        $label
    );
}