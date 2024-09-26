<?php
/**
 * Default template for the [ux_membership_row] shortcode.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */

// Get the default date format from WordPress settings
$dateFormat = get_option('date_format');

/**
 * There may be multiple memberships returned depending on the parameters fed to the shortcode. 
 * In this case, we want to output a row for each membership.
 */
    foreach($args['memberships'] as $membership) {

        if ( isset($membership['end_date']) ) {
            $endDate = date_create($membership['end_date']);

            // Format the datetime value using the default date format
            $formattedDate = date_i18n($dateFormat, $endDate);
        } else {
            $formattedDate = '-';
        }

        // Build the renewal link
        // If cid and cs are used for user authentication, the renewal links will also contain them.
        // Logged in users do not need a checksum in their link.
        $queryArgs = [
            'cid' => $args['cid'],
            'cs' => $args['cs'],
            'mid' => $membership['id']
        ];
        $renewalUrl = add_query_arg( $queryArgs, $args['renewal_url'] );
?>

<tr>
    <td><?php echo $membership['contact_owner.display_name'] ?? $membership['contact.display_name']; ?></td>
    <td><?php echo $membership['membership_type_id:label']; ?></td>
    <td><?php echo $membership['status_id:label']; ?></td>
    <td><?php echo $formattedDate; ?></td>
    <td><a href="<?php echo esc_url($renewalUrl); ?>" target="_blank">Renew this Membership</a></td>
</tr>

<?php
    }
?>

