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

<figure class="wp-block-table">
    <table class="civicrm-ux-membership wp-block-table__table">
        <thead>
            <tr>
                <th>Member</th>
                <th>Membership Type</th>
                <th>Membership Status</th>
                <th>Expiry Date</th>
                <th>Renewal Link</th>
            </tr>
        </thead>
        <tbody>
            <?= wp_kses_post($args['content']); ?>
        </tbody>
    </table>
</figure>