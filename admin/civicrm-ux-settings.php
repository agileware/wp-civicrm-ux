<?php

/**
 * 
 * Settings file.
 * 
 * Builds and renders the plugin settings page.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

namespace CiviCRM_UX;

require_once plugin_dir_path( __FILE__ ) . 'class-civicrm-ux-settings-tabs.php';

// Hook to add admin menu
add_action( 'admin_menu', 'CiviCRM_UX\add_settings_page' );
// Hook to register settings
add_action( 'admin_init', 'CiviCRM_UX\register_settings' );

const MENU_SLUG = 'civicrm-ux-settings';

function add_settings_page() {
    add_options_page(
        'CiviCRM UX Settings',
        'CiviCRM UX Settings',
        'administrator',
        MENU_SLUG,
        __NAMESPACE__ . '\render__settings_page'
    );
}

/**
 * Register settings from settings files.
 */
function register_settings() {
    $settings_dir = plugin_dir_path( __FILE__ ) . 'settings/';

    foreach ( glob( $settings_dir . '*.php' ) as $file ) {
        require_once $file;
    }
}

/**
 * Render the settings page.
 */
function render__settings_page() {
    $tabs = SettingsTabs::get_all();
    $current_tab = $_GET['tab'] ?? 'general';

    ?>
    <div class="wrap">
        <h1>CiviCRM UX Settings</h1>
        <nav class="nav-tab-wrapper">
        <?php 
            // Render tab navigation
            foreach ( $tabs as $slug => $title ) {
                $active = ( $current_tab === $slug ) ? 'nav-tab-active' : '';
                $url = admin_url( 'options-general.php?page=' . MENU_SLUG . '&tab=' . $slug );
                echo "<a class='nav-tab $active' href='$url'>$title</a>";
            }
        ?>
        </nav>
        <form method="post" action="options.php">
            <?php
                settings_fields( "civicrm_ux_options_group_$current_tab" );
                do_settings_sections( "civicrm-ux-settings-$current_tab" );

                if ( $current_tab !== 'general') {
                    submit_button();
                }
            ?>
        </form>
    </div>
    <?php
}