<?php
/**
 * There may be multiple memberships returned depending on the parameters fed to the shortcode. 
 * In this case, we want to output a row for each membership.
 */
    foreach($args['memberships'] as $membership) {
        var_dump($memberships,true);
?>

<tr>
    <td><?php echo $membership['contact.display_name']; ?></td>
    <td><?php echo $membership['membership_type_id:label']; ?></td>
    <td><?php echo $membership['status_id:label']; ?></td>
    <td><?php echo $args['expiry_date']; ?></td>
    <td><a href="<?php echo $args['renewal_url']; ?>" target="_blank">Renew this Membership</a></td>
</tr>

<?php
    }
?>

