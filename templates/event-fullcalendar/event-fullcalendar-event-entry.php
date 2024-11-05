<?php
/**
 * @file Single event entry for display on calendar grid
 *
 * @var $args
 */

$event = $args['event'];
?>

<div class="event-holder">
    <div class="fc-event-title">
        <a class="civicrm-ux-event-link" href="<?= $event['url'] ?>"><?= $event['title'] ?></a>
    </div>
</div>