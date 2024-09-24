<?php
/**
 * Default template for the [ux_membership_row] shortcode.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */


/**
 * There may be multiple memberships returned depending on the parameters fed to the shortcode. 
 * In this case, we want to output a row for each membership.
 */
    foreach($args['memberships'] as $membership) {

        if ( isset($membership['end_date']) ) {
            $endDate = date_create($membership['end_date']);
            $formattedDate = date_format($endDate,"j M, Y");
        } else {
            $formattedDate = '-';
        }

?>

<tr>
    <td><?php echo $membership['contact_owner.display_name'] ?? $membership['contact.display_name']; ?></td>
    <td><?php echo $membership['membership_type_id:label']; ?></td>
    <td><?php echo $membership['status_id:label']; ?></td>
    <td><?php echo $formattedDate; ?></td>
    <td><a href="<?php echo $args['renewal_url']; ?>" target="_blank">Renew this Membership</a></td>
</tr>

<?php
    }
?>

