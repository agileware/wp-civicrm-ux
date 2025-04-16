<?php

/**
 * 
 * Automatically builds tabs for each settings file autoloaded.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

namespace CiviCRM_UX;

class SettingsTabs {
    protected static array $tabs = [];
    protected static ?string $default = null;

    /**
     * Register a settings tab.
     *
     * @param string $slug  The tab slug (e.g., 'ssc').
     * @param string $label The tab label (e.g., 'Self Serve Checksum').
     * @param int    $priority Optional priority (lower = earlier in list).
     */
    public static function register( string $slug, string $label, int $priority = 10 ) {
        self::$tabs[ $slug ] = [
            'label'    => $label,
            'priority' => $priority,
        ];
    }

    /**
     * Get all registered tabs, sorted by priority.
     *
     * @return array [ slug => label ]
     */
    public static function get_all() {
        uasort( self::$tabs, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );
        return array_map(fn( $tab ) => $tab['label'], self::$tabs );
    }

    /**
     * Get the default tab slug.
     *
     * @return string
     */
    public static function get_default() {
        // Use explicitly set default if available
        if ( self::$default !== null ) {
            return self::$default;
        }

        // Otherwise return the slug with the lowest priority
        if ( empty( self::$tabs ) ) {
            return '';
        }

        $sorted = self::$tabs;
        uasort( $sorted, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

        return array_key_first( $sorted );
    }

    /**
     * Set the default tab.
     */
    public static function set_default( string $slug ) {
        if ( isset( self::$tabs[ $slug ] ) ) {
            self::$default = $slug;
        }
    }
}