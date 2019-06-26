<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://agileware.com.au
 * @since      1.0.0
 *
 * @package    Civicrm_Ux
 * @subpackage Civicrm_Ux/admin/partials
 */

// create custom plugin settings menu
add_action( 'admin_menu', 'civicrm_ux_create_menu' );

//call register settings function
add_action( 'admin_init', 'civicrm_ux_register_settings' );
function civicrm_ux_create_menu() {

	//create new top-level menu
	add_options_page( 'CiviCRM UX Setting', 'CiviCRM UX', 'administrator', 'civicrm-ux-setting.php', 'civicrm_ux_settings_page' );
}


function civicrm_ux_register_settings() {
	//register our settings
	register_setting( 'civicrm-ux-settings-group', Civicrm_Ux_Shortcode_ICal_Feed::HASH_OPTION );
}

function civicrm_ux_settings_page() {
	$url_param = [
		'hash' => get_option( Civicrm_Ux_Shortcode_ICal_Feed::HASH_OPTION ),
	];
	$url       = add_query_arg(
		$url_param,
		get_rest_url( null,
			'/' . Civicrm_Ux_Shortcode_ICal_Feed::API_NAMESPACE . '/' . Civicrm_Ux_Shortcode_ICal_Feed::INTERNAL_ENDPOINT ) );
	?>
    <div class="wrap">
        <h1>CiviCRM UX</h1>
        <form method="post" action="options.php">
			<?php settings_fields( 'civicrm-ux-settings-group' ); ?>
			<?php do_settings_sections( 'civicrm-ux-settings-group' ); ?>
            <div>
                <h2>iCal Feed</h2>
                <ul>
                    <li>
                        <label>Hash
                            <input id="ical-hash-field" type="text" name="ical_hash" size="40"
                                   value="<?php echo esc_attr( get_option( Civicrm_Ux_Shortcode_ICal_Feed::HASH_OPTION ) ); ?>"/>
                        </label>
                        <!-- TODO fix button -->
                        <button id="generate-ical-hash">GENERATE</button>
                    </li>
                    <li>
                        <span>The internal feed url is: </span>
                        <a id="ical-internal-url" href="<?php echo $url ?>"><?php echo $url ?></a>
                    </li>
                </ul>
            </div>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }