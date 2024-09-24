<?php

/**
 * Defines Template related functions.
 * 
 */

/**
 * Loads a template part.
 * 
 * @param string $slug Defines the type of template. Templates should be organised into directories with the slug as directory name.
 * @param string $name Name of the template.
 * @param array $args Arguments to pass to the template.
 * 
 */
function civicrm_ux_load_template_part( $slug, $name, $args = array() ) {
    // Allow themes to override the plugin's template part
    $template = locate_template( $slug . '-' . $name . '.php' );

    // If not found in the theme, load from the plugin's template directory
    if ( ! $template ) {
        $template = WP_CIVICRM_UX_PLUGIN_PATH . 'templates/' . $slug . '/' . $slug . '-' . $name . '.php';
    }

    // Extract arguments to be used in the template
    if ( ! empty( $args ) ) {
        extract( $args );
    }

    /**
     * Load the template file
     * 
     * NOTE: We use include here instead of load_template(), since $args cannot be passed to load_template()
     * explicitly, and we want to avoid loading the values into global variables for load_template() to access.
     */
    if ( file_exists( $template ) ) {
        //include $template;
        load_template($template, false, $args);
    }
}