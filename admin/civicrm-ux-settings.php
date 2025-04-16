<?php

namespace CiviCRM_UX;

// Hook to add admin menu
add_action( 'admin_menu', 'CiviCRM_UX\add_settings_page' );
// Hook to register settings
add_action( 'admin_init', 'CiviCRM_UX\register_settings' );

function add_settings_page() {
    add_options_page(
        'CiviCRM UX Settings',       // Page title
        'CiviCRM UX Settings',                // Menu title
        'administrator',           // Capability
        'civicrm-ux-settings-new',        // Menu slug
        __NAMESPACE__ . '\render__settings_page' // Callback to render the page
    );
}

function get_settings_tabs() {
    return [
        'general' => 'General',
        'memberships' => 'Memberships',
        'contributions' => 'Contributions',
        'ssc'  => 'Self Serve Checksum',
        'cf-turnstile'  => 'Cloudflare Turnstile',
        'ical' => 'iCal Feed',
        'plugin-activation-blocks' => 'Plugin Activation Blocks',
    ];
}

function register_settings() {
    // Register the settings
    $settings_dir = plugin_dir_path( __FILE__ ) . 'settings/';

    foreach ( glob( $settings_dir . '*.php' ) as $file ) {
        require_once $file;
    }

    // Load additional scripts
    require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-activation-blocks.php';
}

/**
 * Render the settings page.
 */
function render__settings_page() {
    $tabs = get_settings_tabs();
    $current_tab = $_GET['tab'] ?? 'general';

    ?>
    <div class="wrap">
        <h1>CiviCRM UX Settings</h1>
        <nav class="nav-tab-wrapper">
        <?php 
            // Render tab navigation
            foreach ( $tabs as $slug => $title ) {
                $active = ( $current_tab === $slug ) ? 'nav-tab-active' : '';
                $url = admin_url( 'options-general.php?page=civicrm-ux-settings-new&tab=' . $slug );
                echo "<a class='nav-tab $active' href='$url'>$title</a>";
            }
        ?>
        </nav>
        <form method="post" action="options.php">
            <?php
                settings_fields( "civicrm_ux_options_group_$current_tab" );
                do_settings_sections( "civicrm-ux-settings-new-$current_tab" );

                if ( $current_tab !== 'general') {
                    submit_button();
                }
            ?>
        </form>
    </div>
    <?php
}