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
function civicrm_ux_load_template_part( $slug, $name, $args = [] ) {
    do_action( "get_template_part_{$slug}", $slug, $name, $args );

    $templates = [
        ...array_merge(...array_map(function ($prefix) use ($slug, $name) {
            return [
                "{$prefix}/{$slug}/{$slug}-{$name}.php",
                "{$prefix}/{$slug}/{$name}.php",
                "{$prefix}/{$slug}-{$name}.php",
            ];
        }, ['templates', 'template-parts'])),
        "{$slug}-{$name}.php" ];

	do_action( 'get_template_part', $slug, $name, $templates, $args );

    // Allow themes to override the plugin's template part
    $template = locate_template( $templates );

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
     */
    if ( file_exists( $template ) ) {
        load_template($template, false, $args);
    }
}

function civicrm_ux_get_template_part( $slug, $name, $args = [] ) {
    ob_start();

    civicrm_ux_load_template_part( $slug, $name, $args );

    return ob_get_clean();
}