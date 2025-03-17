<?php
/**
 * @file Template file for single event output
 *
 * @var $event
 * @var $image_src_field
 * @var $image_url
 * @var $colors
 * @var $event_start_day
 * @var $event_start_hour
 * @var $event_end_day
 * @var $event_end_hour
 * @var $event_location
 * @var $url
 */

extract($args);
?>
<div class="civicrm-ux-event-listing" colspan="3">
    <?php if ( !empty($image_url) ) : ?>
        <div class="civicrm-ux-event-listing-image"><img src="<?= $image_url ?>" alt="Event Image"></div>
    <?php endif; ?>

    <div class="civicrm-ux-event-listing-details-summary">
        <div class="civicrm-ux-event-listing-details">
            <div class="civicrm-ux-event-listing-type" style="background-color: <?= $colors[$event['event_type_id:label']] ?? $colors['default'] ?>;">
                <?= $event['event_type_id:label']; ?>
            </div>
            <div class="civicrm-ux-event-listing-name"><?= $event['title']; ?></div>
            <div class="civicrm-ux-event-listing-date">
                <i class="fa fa-calendar-o"></i>
                <span class="event-time-text"><?= $event_start_day ?>
            <?php if ($event_start_day == $event_end_day && $event_start_hour == $event_end_hour) : ?>
                <i class="fa fa-clock-o"></i><?= $event_start_hour ?>
            <?php elseif ($event_start_day == $event_end_day) : ?>
                <i class="fa fa-clock-o"></i><?= $event_start_hour ?> to <?= $event_end_hour ?>
            <?php else : ?>
                <i class="fa fa-clock-o"></i><?= $event_start_hour ?> to <i class="fa fa-calendar-o"></i><?= $event_end_day ?><i class="fa fa-clock-o"></i><?= $event_end_hour ?>
            <?php endif; ?>
            </div>

            <?php if (!empty($event_location)) : ?>
                <div class="civicrm-ux-event-listing-location">
                    <i class="fa fa-map-marker"></i>
                    <span class="event-time-text"><?= $event_location ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="civicrm-ux-event-listing-buttons">
            <?php if (!empty($event['is_online_registration'])) : ?>
                <button class="civicrm-ux-event-listing-register" onclick="window.location.href='<?= $url ?>'">
                    Click here to register
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="civicrm-ux-event-listing-desc">
        <?= !empty($event['description']) ? $event['description'] : 'No event description provided' ?>
    </div>
    <hr>
</div>
