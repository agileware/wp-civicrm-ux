<?php
/**
 * Default template for the [ux_membership] shortcode.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

?>

<table class="civicrm-ux-membership">
    <tr>
        <th>Member</th>
        <th>Membership Type</th>
        <th>Membership Status</th>
        <th>Expiry Date</th>
        <th>Renewal Link</th>
    </tr>
    <?= wp_kses_post($args['content']); ?>
</table>