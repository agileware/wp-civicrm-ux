<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Agileware_Civicrm_Utilities
 * @subpackage Agileware_Civicrm_Utilities/admin/partials
 */

// create custom plugin settings menu
add_action( 'admin_menu', 'agileware_civicrm_utilities_create_menu' );

function agileware_civicrm_utilities_create_menu() {

	//create new top-level menu
	add_menu_page( 'Agileware CiviCRM Utilities Settings', 'Agileware CiviCRM Utilities Settings', 'administrator', __FILE__, 'agileware_civicrm_utilities_settings_page' );

	//call register settings function
	add_action( 'admin_init', 'agileware_civicrm_utilities_register_settings' );
}


function agileware_civicrm_utilities_register_settings() {
	//register our settings
	register_setting( 'agileware-civicrm-utilities-settings-group', Agileware_Civicrm_Utilities_Shortcode_ICal_Feed::HASH_OPTION );
}

function agileware_civicrm_utilities_settings_page() {
	$url_param = [
		'hash' => get_option( Agileware_Civicrm_Utilities_Shortcode_ICal_Feed::HASH_OPTION ),
	];
	$url       = add_query_arg(
		$url_param,
		get_rest_url( NULL,
			'/' . Agileware_Civicrm_Utilities_Shortcode_ICal_Feed::API_NAMESPACE . '/' . Agileware_Civicrm_Utilities_Shortcode_ICal_Feed::INTERNAL_ENDPOINT ) );
	?>
    <div class="wrap">
        <h1>Agileware CiviCRM Utilities</h1>

        <form method="post" action="options.php">
			<?php settings_fields( 'agileware-civicrm-utilities-settings-group' ); ?>
			<?php do_settings_sections( 'agileware-civicrm-utilities-settings-group' ); ?>
            <ul>
                <li>
                    <label>ICal Hash
                        <input id="ical-hash-field" type="text" name="ical_hash" value="<?php echo esc_attr( get_option( Agileware_Civicrm_Utilities_Shortcode_ICal_Feed::HASH_OPTION ) ); ?>"/>
                    </label>
                    <button id="generate-ical-hash">GENERATE</button>
                </li>
                <li>
                    <span>The internal feed url is: </span>
                    <a id="ical-internal-url" href="<?php echo $url ?>"><?php echo $url ?></a>
                </li>
            </ul>

        </form>
    </div>
<?php }