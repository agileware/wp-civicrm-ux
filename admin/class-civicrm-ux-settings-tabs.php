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

    /**
     * Register a settings tab.
     *
     * @param string $slug  The tab slug (e.g., 'ssc').
     * @param string $label The tab label (e.g., 'Self Serve Checksum').
     * @param int    $priority Optional priority (lower = earlier in list).
     */
    public static function register( string $slug, string $label, int $priority = 10 ): void {
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
    public static function get_all(): array {
        uasort( self::$tabs, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );
        return array_map(fn( $tab ) => $tab['label'], self::$tabs );
    }
}