<?php
/**
 * @file Template file for single event output
 *
 * @var $event
 * @var $image_src_field
 * @var $image_url
 * @var $colors
 * @var $event_time
 * @var $event_location
 * @var $url
 */
?>
<div class="civicrm-ux-event-listing">
    <?php if ( $image_url ) : ?>
        <div class="civicrm-ux-event-listing-image"><img src="<?= $image_url ?>" alt="Event Image"></div>
    <?php endif; ?>

    <div class="civicrm-ux-event-listing-details-summary">
        <div class="civicrm-ux-event-listing-details">
            <div class="civicrm-ux-event-listing-type" style="--ux-event-color: <?= $colors[$event['event_type_id:label']] ?? $colors['default'] ?>;">
                <?= $event['event_type_id:label']; ?>
            </div>
            <div class="civicrm-ux-event-listing-name"><?= $event['title']; ?></div>
            <div class="civicrm-ux-event-listing-date">
                <i class="fa fa-calendar-o"></i>
                <span class="event-time-text"><?= $event_time ?></span>
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
