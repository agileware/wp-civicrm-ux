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

    $extensions = [ 'php', 'html' ];
    $locations  = [ 'templates', 'template-parts' ];

    // Build all possible template file paths
    $templates = [];

    foreach ( $locations as $prefix ) {
        foreach ( $extensions as $ext ) {
            $templates[] = "{$prefix}/{$slug}/{$slug}-{$name}.{$ext}";
            $templates[] = "{$prefix}/{$slug}/{$name}.{$ext}";
            $templates[] = "{$prefix}/{$slug}-{$name}.{$ext}";
        }
    }

    // One final fallback (unscoped) option
    foreach ( $extensions as $ext ) {
        $templates[] = "{$slug}-{$name}.{$ext}";
    }

	do_action( 'get_template_part', $slug, $name, $templates, $args );

    // Allow themes to override the plugin's template part
    $template = locate_template( $templates );

    // If not found in the theme, try loading from the plugin's template directory
    if ( ! $template ) {
        $base_path = trailingslashit( WP_CIVICRM_UX_PLUGIN_PATH ) . "templates/{$slug}/";
    
        foreach ( $extensions as $ext ) {
            foreach ( [ "{$slug}-{$name}.{$ext}", "{$name}.{$ext}" ] as $filename ) {
                $full_path = $base_path . $filename;
    
                if ( file_exists( $full_path ) ) {
                    $template = $full_path;
                    break 2; // exit both loops
                }
            }
        }
    }

    if ( ! $template || ! file_exists( $template ) ) {
        return; // Bail silently if nothing found
    }

    // Extract arguments to be used in the template
    if ( ! empty( $args ) ) {
        extract( $args );
    }

    load_template($template, false, $args);
}

function civicrm_ux_get_template_part( $slug, $name, $args = [] ) {
    ob_start();

    civicrm_ux_load_template_part( $slug, $name, $args );

    return ob_get_clean();
}