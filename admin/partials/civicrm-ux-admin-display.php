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
                <h2>Self Serve Checksum Form</h2>
                <p>The [self_serve_checksum] shortcode can be used to protect your forms and force a valid checksum before form submission. Users must provide their email address and request a link with their checksum token to be sent to their email to access the form.</p>
                <p>Configure the contents of the form and the email to be sent below.</p>
                <table>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2">
                            <label for="self_serve_checksum_form_text">Form Text</label>
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
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2">
                            <label for="self_serve_checksum_email_subject">Email Subject</label>
                            <input type="text"
                                   id="self_serve_checksum_email_subject"
                                   name="self_serve_checksum[email_subject]"
                                   value="<?php echo $self_serve_checksum['email_subject']; ?>"/>
                        </td>
                    </tr>
                    <tr style="vertical-align: text-top;">
                        <td colspan="2">
                            <label for="self_serve_checksum_email_message">Email Message</label>
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
                        </td>
                    </tr>
                </table>
            </div>
          <?php submit_button(); ?>
        </form>
    </div>
<?php }
