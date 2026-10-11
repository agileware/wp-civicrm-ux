<?php

/**
 * Defines Template related functions.
 * 
 */

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

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

    // The arguments are NOT extracted into this function's scope. extract() here used the
    // caller's own keys, so an $args key named 'template' (which reaches here as raw shortcode
    // attributes from some callers) would overwrite the $template path computed above and get
    // require'd by load_template() below - a local file inclusion. load_template() passes $args
    // into the template, where it is read as $args or extracted by the template itself, so
    // extracting here was never needed for the template to work.

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