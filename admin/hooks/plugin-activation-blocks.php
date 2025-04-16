<?php

namespace CiviCRM_UX\PluginActivationBlocks;

// WPCIVIUX-148 Implement plugin activation block
add_action( 'admin_init', __NAMESPACE__ . '\deactivate_blacklisted_plugins' );
add_action( 'activate_plugin', __NAMESPACE__ . '\prevent_blacklisted_plugin_activation', 10, 2 );

/**
 * A list of plugins blacklisted from activation.
 * 
 * Define plugin paths here.
 * Define the corresponding defaults for civicrm_plugin_activation_blocks setting in Civicrm_Ux_Option_Store.
 */
function get_blocked_plugins() {
    return [
        'event_tickets' => 'event-tickets/event-tickets.php',
    ];
}


/**
 * Blacklist plugins from activating
 * 
 * WPCIVIUX-148 : event-tickets
 */
function prevent_blacklisted_plugin_activation($plugin, $network_wide) {
    $blocked_plugins = get_blocked_plugins();

    // Get the list of plugins to block
    $plugin_activation_blocks = get_option( 'civicrm_plugin_activation_blocks', [] );

    $plugin_activation_blocks = is_array($plugin_activation_blocks) ? $plugin_activation_blocks : [];

    // Get the plugin slug from the plugin path
    $plugin_slug = str_replace('-', '_', dirname($plugin));

    // Check if the plugin is in the list of blocked plugins and if it should be blocked
    if (isset($blocked_plugins[$plugin_slug]) && isset($plugin_activation_blocks[$plugin_slug]) && $plugin_activation_blocks[$plugin_slug]) {
        // Abort the activation process and display an error message
        wp_die(__('This plugin is blocked and cannot be activated.', 'textdomain'), __('Plugin Activation Error', 'textdomain'), array('back_link' => true));
    }
}

/**
 * Deactivate currently active blacklisted plugins.
 * 
 * This is in case plugins have been blacklisted after they were already activated.
 * 
 * WPCIVIUX-148 : event-tickets
 */
function deactivate_blacklisted_plugins() {
    $blocked_plugins = get_blocked_plugins();

    // Get the list of plugins to block
    $plugin_activation_blocks = get_option( 'civicrm_plugin_activation_blocks', [] );

    $plugin_activation_blocks = is_array($plugin_activation_blocks) ? $plugin_activation_blocks : [];

    // Get the list of currently active plugins
    $active_plugins = get_option('active_plugins', array());

    // Loop through the list of plugins and remove any blocked plugins
    foreach ( $blocked_plugins as $blocked_plugin => $path) {
        if (!isset($plugin_activation_blocks[$blocked_plugin]) || !$plugin_activation_blocks[$blocked_plugin]) {
            continue;
        }

        $index = array_search( $path, $active_plugins );
        if ( false !== $index ) {
            deactivate_plugins( $path );
        }
    }
}