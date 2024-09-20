<?php

/**
 * Provide a admin area view for CiviCRM UX
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
    'hash' => get_option(Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION),
  ];
  $url       = add_query_arg(
    $url_param,
    get_rest_url(NULL,
      '/' . Civicrm_Ux_Shortcode_Event_ICal_Feed::API_NAMESPACE . '/' . Civicrm_Ux_Shortcode_Event_ICal_Feed::INTERNAL_ENDPOINT));
  $opt_summary_options       = Civicrm_Ux::getInstance()
                         ->get_store()
                         ->get_option('civicrm_summary_options');
  $opt_contribution_ux     = Civicrm_Ux::getInstance()
                         ->get_store()
                         ->get_option('civicrm_contribution_ux');

  $plugin_activation_blocks = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option('civicrm_plugin_activation_blocks');

  $self_serve_checksum = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option('self_serve_checksum');

  $civicrm_ux_cf_turnstile = Civicrm_Ux::getInstance()
                        ->get_store()
                        ->get_option('civicrm_ux_cf_turnstile');


  ?>
    <div class="wrap">
        <h1>CiviCRM UX</h1>
        <h2>Usage Documentation</h2>
        <p>
            Documentation on the available shortcodes and functionality for this plugin is available on our Github project page, <a href="https://github.com/agileware/wp-civicrm-ux/blob/master/USAGE.md" target="_blank">CiviCRM UX User Guide</a>.
        </p>
        <h2>How To Say Thank You!</h2>
        <p>
            If you are using this plugin and find it useful, you can say thank you by <a href="https://www.paypal.me/agileware" target="_blank">buying us a beer or a coffee</a>.
        </p>
        <form method="post" action="options.php">
          <?php settings_fields('civicrm-ux-settings-group'); ?>
            <div>
                <h2>iCal Feed</h2>
                <table>
                    <tr>
                        <td><label for="ical-hash-field">Random Hash</label>
                        </td>
                        <td><input id="ical-hash-field" type="text"
                                   name=<?php echo '"' . Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION . '"' ?> size="40"
                                   value="<?php echo esc_attr(get_option(Civicrm_Ux_Shortcode_Event_ICal_Feed::HASH_OPTION)); ?>"/>
                            <!-- TODO fix button -->
                            <button id="generate-ical-hash" type="button">
                                Re-generate Hash
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>The internal feed url is:</td>
                        <td><a id="ical-internal-url"
                               href="<?php echo $url ?>"><?php echo $url ?></a>
                        </td>
                    </tr>
                </table>
                <h2>Membership Summary</h2>
                <table>
                    <tr style="vertical-align: text-top;">
                        <td><label for="civicrm_summary_show_renewal_date">The
                                number of days to show the renewal notice and
                                URL</label></td>
                        <td><input type="number" min="0" step="1"
                                   id="civicrm_summary_show_renewal_date"
                                   name="civicrm_summary_options[civicrm_summary_show_renewal_date]"
                                   value="<?php echo $opt_summary_options['civicrm_summary_show_renewal_date']; ?>"/>
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td><label for="civicrm_summary_membership_join_URL">URL
                                for the Membership Join page</label></td>
                        <td><input type="text"
                                   id="civicrm_summary_membership_join_URL"
                                   name="civicrm_summary_options[civicrm_summary_membership_join_URL]"
                                   value="<?php echo $opt_summary_options['civicrm_summary_membership_join_URL']; ?>"/>
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td><label for="civicrm_summary_membership_renew_URL">URL
                                for the Membership Renewal page</label></td>
                        <td><input type="text"
                                   id="civicrm_summary_membership_renew_URL"
                                   name="civicrm_summary_options[civicrm_summary_membership_renew_URL]"
                                   value="<?php echo $opt_summary_options['civicrm_summary_membership_renew_URL']; ?>"/>
                        </td>
                    </tr>
                </table>
                <h2>Contribution Page Tweaks</h2>
                <table>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2"><input type="checkbox" id="is_recur_default" name="civicrm_contribution_ux[is_recur_default]"
                            <?php echo isset($opt_contribution_ux['is_recur_default']) ? ' checked="checked">' : '>'; ?>
                            <label for="is_recur_default">For all Contribution Pages, set the payment to recurring by default.</label></td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2"><input type="checkbox" id="is_autorenew_default" name="civicrm_contribution_ux[is_autorenew_default]"
                            <?php echo isset($opt_contribution_ux['is_autorenew_default']) ? ' checked="checked">' : '>'; ?>
                            <label for="is_autorenew_default">For all Membership Contribution Pages, set the membership to auto-renew by default.</label>
                        </td>
                    </tr>
                </table>
                <h2>Plugin Activation Blocks</h2>
                <p>Prevent the following plugins from being activated.</p>
                <table>
                    <?php
                        // Add extra plugin options here.
                        // Defaults set in /includes/class-civicrm-ux-option-store.php
                    ?>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2"><input type="checkbox" id="event_tickets" name="civicrm_plugin_activation_blocks[event_tickets]"
                            <?php echo isset($plugin_activation_blocks['event_tickets']) && $plugin_activation_blocks['event_tickets'] ? ' checked="checked">' : '>'; ?>
                            <label for="event_tickets">Event Tickets</label></td>
                    </tr>
                </table>
                <h2>Cloudflare Turnstile</h2>
                <p>Protect your Self Serve Checksum Form against spam using Cloudflare Turnstile.</p>
                <p><a href="https://www.cloudflare.com/en-gb/products/turnstile/" target="_blank">Learn more about this free service.</a></p>
                <p>Create a widget and get your sitekey and secret key from <a href="https://dash.cloudflare.com/?to=/:account/turnstile" target="_blank">https://dash.cloudflare.com/?to=/:account/turnstile</a></p>
                <table>
                    <tr style="vertical-align: text-top;">
                        <td><label for="sitekey">Sitekey</label></td>
                        <td><input type="text"
                                   id="sitekey"
                                   name="civicrm_ux_cf_turnstile[sitekey]"
                                   value="<?php echo $civicrm_ux_cf_turnstile['sitekey']; ?>"/>
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td><label for="secret_key">Secret Key</label></td>
                        <td><input type="text"
                                   id="secret_key"
                                   name="civicrm_ux_cf_turnstile[secret_key]"
                                   value="<?php echo $civicrm_ux_cf_turnstile['secret_key']; ?>"/>
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td><label for="theme_select">Theme</label></td>
                        <td><select name="civicrm_ux_cf_turnstile[theme]" id="theme_select">
                                <option value="auto" <?php selected($civicrm_ux_cf_turnstile['theme'], 'auto'); ?>>Auto</option>
                                <option value="light" <?php selected($civicrm_ux_cf_turnstile['theme'], 'light'); ?>>Light</option>
                                <option value="dark" <?php selected($civicrm_ux_cf_turnstile['theme'], 'dark'); ?>>Dark</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <h2>Self Serve Checksum Form</h2>
                <p>The <code>[ux_self_serve_checksum]</code> shortcode can be used to protect your forms and force a valid checksum before form submission.</p>
                <p>Read more on how to use the shortcode in the <a href="https://github.com/agileware/wp-civicrm-ux/blob/GFCV-107/USAGE.md#self-serve-checksum-shortcode" target="_blank">CiviCRM UX User Guide.</a></p>
                <p>Configure the contents of the Self Serve Checksum request form and the email to be sent to the user below.</p>
                <h3>Protection Form</h3>
                <table width="100%">
                    <colgroup>
                        <col class="col-label" width="200px" />
                        <col />
                        <col class="col-value" />
                    </colgroup>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2">
                            <label for="self_serve_checksum_form_text" style="font-size: 1.2em; font-weight:500;">Form Text</label>
                        </td>
                        <td>
                            <?php
                            wp_editor(
                                $self_serve_checksum['form_text'],      // Content of the editor
                                'self_serve_checksum_form_text',      // Unique ID for the editor
                                array(
                                    'textarea_name' => "self_serve_checksum[form_text]", // Name used to save the content
                                    'media_buttons' => false,   // Show media upload buttons
                                    'textarea_rows' => 10,     // Set the number of rows in the editor
                                    'teeny' => false,          // Use the full editor toolbar
                                    'quicktags' => true        // Enable quicktags (HTML editor)
                                )
                            );
                            ?>
                            <p>Appears above the Self Serve Checksum protection form.</p>
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2">
                            <label for="self_serve_checksum_form_confirmation_text" style="font-size: 1.2em; font-weight:500;">Confirmation Text</label>
                        </td>
                        <td>
                            <?php
                            wp_editor(
                                $self_serve_checksum['form_confirmation_text'],      // Content of the editor
                                'self_serve_checksum_form_confirmation_text',      // Unique ID for the editor
                                array(
                                    'textarea_name' => "self_serve_checksum[form_confirmation_text]", // Name used to save the content
                                    'media_buttons' => false,   // Show media upload buttons
                                    'textarea_rows' => 10,     // Set the number of rows in the editor
                                    'teeny' => false,          // Use the full editor toolbar
                                    'quicktags' => true        // Enable quicktags (HTML editor)
                                )
                            );
                            ?>
                            <p>Informs the user that their request to view the protected form has been confirmed. An email will be sent to them with the link.</p>
                            <p>The following tokens are available to help you build your content:</p>
                            <ul>
                                <li><code>{email_address}</code> - Outputs the email address the user provided.</li>
                                <li><code>{page_title}</code> - Outputs the title of the form page the email was requested from.</li>
                            </ul>
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2">
                            <label for="self_serve_checksum_form_invalid_contact_text" style="font-size: 1.2em; font-weight:500;">Invalid Contact Text</label>
                        </td>
                        <td>
                            <?php
                            wp_editor(
                                $self_serve_checksum['form_invalid_contact_text'],      // Content of the editor
                                'self_serve_checksum_form_invalid_contact_text',      // Unique ID for the editor
                                array(
                                    'textarea_name' => "self_serve_checksum[form_invalid_contact_text]", // Name used to save the content
                                    'media_buttons' => false,   // Show media upload buttons
                                    'textarea_rows' => 10,     // Set the number of rows in the editor
                                    'teeny' => false,          // Use the full editor toolbar
                                    'quicktags' => true        // Enable quicktags (HTML editor)
                                )
                            );
                            ?>
                            <p>Informs the user that their email address is not currently in your CiviCRM records. No email will be sent to them.</p>
                            <p>The following tokens are available to help you build your content:</p>
                            <ul>
                                <li><code>{email_address}</code> - Outputs the email address the user provided.</li>
                                <li><code>{page_title}</code> - Outputs the title of the form page the email was requested from.</li>
                            </ul>
                        </td>
                    </tr>
                </table>
                <h3>Self Serve Checksum Email</h3>
                <table width="100%">
                    <colgroup>
                        <col class="col-label" width="200px" />
                        <col />
                        <col class="col-value" />
                    </colgroup>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2">
                            <label for="self_serve_checksum_email_message" style="font-size: 1.2em; font-weight:500;">Email Message</label>
                        </td>
                        <td>
                            <?php
                            wp_editor(
                                $self_serve_checksum['email_message'],      // Content of the editor
                                'self_serve_checksum_email_message',      // Unique ID for the editor
                                array(
                                    'textarea_name' => "self_serve_checksum[email_message]", // Name used to save the content
                                    'media_buttons' => true,   // Show media upload buttons
                                    'textarea_rows' => 10,     // Set the number of rows in the editor
                                    'teeny' => false,          // Use the full editor toolbar
                                    'quicktags' => true        // Enable quicktags (HTML editor)
                                )
                            );
                            ?>
                            <p>Use the following tokens to build your email message to the user:</p>
                            <ul>
                                <li><code>{checksum_url}</code> - <strong>Required.</strong> Outputs the unique URL for the contact to complete the form.</li>
                                <li><code>{page_title}</code> - Outputs the title of the form page the email was requested from.</li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </div>
          <?php submit_button(); ?>
        </form>
    </div>
<?php }
