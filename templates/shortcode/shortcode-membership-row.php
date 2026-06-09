<?php

/**
 * Default template for the [ux_membership_row] shortcode.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Get the default date format from WordPress settings
$dateFormat = get_option('date_format');

/**
 * There may be multiple memberships returned depending on the parameters fed to the shortcode. 
 * In this case, we want to output a row for each membership.
 */
foreach ($args['memberships'] as $membership) {

    if (!empty($membership['end_date'])) {
        $endDate = date_create($membership['end_date']);

        // Format the datetime value using the default date format
        $formattedEndDate = date_i18n($dateFormat, $endDate->getTimestamp());
    } else {
        $formattedEndDate = '-';
    }

    if (!empty($args['expiration_offset'])) {
        $expiration_offset_date = strtotime($args['expiration_offset']);
    } else {
        $expiration_offset_date = FALSE;
    }

    // Build the renewal link
    // If cid and cs are used for user authentication, the renewal links will also contain them.
    // Logged in users do not need a checksum in their link.
    $renewalUrl = FALSE;
    if (!empty($args['renewal_url'])) {
        $queryArgs = [
            'cid' => $args['cid'],
            'cs' => $args['cs'],
            'mid' => $membership['id']
        ];
        $renewalUrl = add_query_arg($queryArgs, $args['renewal_url']);
    }
?>

    <tr>
        <td><?= esc_html($membership['contact_owner.display_name'] ?? $membership['contact.display_name']); ?></td>
        <td><?= esc_html($membership['membership_type_id:label']); ?></td>
        <td><?= esc_html($membership['status_id:label']); ?></td>
        <td><?= esc_html($formattedEndDate); ?></td>
        <td>
            <?php if ($renewalUrl && !empty($membership['end_date']) && (($expiration_offset_date && $endDate->getTimestamp() <= $expiration_offset_date) || $endDate->getTimestamp() <= time())) { ?>
                <div class="wp-block-button is-style-outline">
                    <a href="<?= esc_url($renewalUrl); ?>" target="_blank" class="wp-block-button__link wp-element-button">Renew this membership</a>
                </div>
            </td>
            <?php } else { ?>
            <p>No renewal required<p></td>
            <?php } ?>
    </tr>
<?php
}
?>