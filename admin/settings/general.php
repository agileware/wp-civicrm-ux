<?php

namespace CiviCRM_UX\Settings\General;

$group  = 'civicrm_ux_options_group';
$page   = 'civicrm-ux-settings-new-general'; // A tab on the page
$setting_key = '';

/**
 * 
 * ADD SECTIONS
 * 
 */
add_settings_section(
    'civicrm_ux_settings_section_usage_docs',
    'Usage Documentation',
    __NAMESPACE__ . '\usage_docs_cb',
    'civicrm-ux-settings-new-general' // menu slug
);

add_settings_section(
    'civicrm_ux_settings_section_thankyou',
    'How To Say Thank You!',
    __NAMESPACE__ . '\thankyou_cb',
    'civicrm-ux-settings-new-general' // menu slug
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