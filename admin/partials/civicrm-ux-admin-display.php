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

function civicrm_ux_settings_page() {
	$url_param = [
		'hash' => get_option( Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION ),
	];
	$url       = add_query_arg(
		$url_param,
		get_rest_url( null,
			'/' . Civicrm_Ux_Shortcode_Event_ICal_Feed::API_NAMESPACE . '/' . Civicrm_Ux_Shortcode_Event_ICal_Feed::INTERNAL_ENDPOINT ) );
	$opt       = Civicrm_Ux::getInstance()->get_store()->get_option( 'civicrm_summary_options' );
	?>
    <div class="wrap">
        <h1>CiviCRM UX</h1>
        <form method="post" action="options.php">
			<?php settings_fields( 'civicrm-ux-settings-group' ); ?>
            <div>
                <h2>iCal Feed</h2>
                <table>
                    <tr>
                        <th scope="row"><label for="ical-hash-field">Hash</label></th>
                        <td><input id="ical-hash-field" type="text"
                                   name=<?php echo '"' . Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION . '"' ?> size="40"
                                   value="<?php echo esc_attr( get_option( Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION ) ); ?>"/>
                            <!-- TODO fix button -->
                            <button id="generate-ical-hash" type="button">GENERATE</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><span>The internal feed url is: </span></th>
                        <td><a id="ical-internal-url" href="<?php echo $url ?>"><?php echo $url ?></a></td>
                    </tr>
                </table>
                <h2>Membership Summary</h2>
                <table>
                    <tr valign="top">
                        <th scope="row"><label for="civicrm_summary_show_renewal_date">The number of days to show the
                                renewal notice and URL</label></th>
                        <td><input type="number" min="0" step="1" id="civicrm_summary_show_renewal_date"
                                   name="civicrm_summary_options[civicrm_summary_show_renewal_date]"
                                   value="<?php echo $opt['civicrm_summary_show_renewal_date']; ?>"/></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label style="float:left;" for="civicrm_summary_membership_join_URL">URL for the
                                membership join page</label></th>
                        <td><input type="text" id="civicrm_summary_membership_join_URL"
                                   name="civicrm_summary_options[civicrm_summary_membership_join_URL]"
                                   value="<?php echo $opt['civicrm_summary_membership_join_URL']; ?>"/></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label style="float:left;" for="civicrm_summary_membership_renew_URL">URL for
                                the membership renewal page</label></th>
                        <td><input type="text" id="civicrm_summary_membership_renew_URL"
                                   name="civicrm_summary_options[civicrm_summary_membership_renew_URL]"
                                   value="<?php echo $opt['civicrm_summary_membership_renew_URL']; ?>"/></td>
                    </tr>
                </table>
            </div>
			<?php submit_button(); ?>
            <div>
                <a href="/wp-content/plugins/civicrm-ux/admin/partials/civicrm-ux-guide.html" target="_blank">CiviCRM UX
                    guide</a>
            </div>
        </form>
    </div>
<?php }
