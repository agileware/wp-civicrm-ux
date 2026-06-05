<?php
/**
 * @file Single event entry for display on calendar grid
 *
 * @var $args
 */

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$event = $args['event'];
?>

<div class="event-holder">
    <div class="fc-event-title">
        <a class="civicrm-ux-event-link" href="<?= esc_url($event['url']); ?>"><?= esc_html($event['title']); ?></a>
    </div>
</div>