<?php

/**
 * 
 * Defines settings.
 * 
 * Auto-loaded from civicrm-ux-settings.php.
 * 
 * @author     Agileware <support@agileware.com.au>
 */

namespace CiviCRM_UX\Settings\General;

use CiviCRM_UX\SettingsTabs;

const SLUG = 'general';
const LABEL = 'General';
const GROUP  = 'civicrm_ux_options_group_general';
const PAGE   = 'civicrm-ux-settings-general'; // A tab on the page
const OPTION_NAME = 'civicrm_ux_general';

SettingsTabs::register( SLUG, LABEL, 0 );
SettingsTabs::set_default( SLUG );

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_usage_docs',
    'Usage Documentation',
    __NAMESPACE__ . '\usage_docs_cb',
    PAGE
);

add_settings_section(
    'civicrm_ux_settings_section_thankyou',
    'How To Say Thank You!',
    __NAMESPACE__ . '\thankyou_cb',
    PAGE
);



/**
 * 
 * CALLBACKS
 * 
 */
function usage_docs_cb() {
    printf( '<p>Documentation on the available shortcodes and functionality for this plugin is available on our Github project page, <a href="%s" target="_blank">CiviCRM UX User Guide</a>.</p>', 
            esc_url('https://github.com/agileware/wp-civicrm-ux/blob/master/USAGE.md') );
}

function thankyou_cb() {
    printf( '<p>If you are using this plugin and find it useful, you can say thank you by <a href="%s" target="_blank">buying us a beer or a coffee</a>.</p>', 
            esc_url('https://www.paypal.me/agileware') );
}