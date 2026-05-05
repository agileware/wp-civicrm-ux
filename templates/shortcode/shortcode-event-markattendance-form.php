<?php
/**
 * Default template for the [ux_event_markattendance] shortcodes to output the form.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */
?>

<form>
    <p><span class="event-title"><?= esc_html($args['event']['title']); ?></span></p>
    <p>Did you attend this event?</p>

    <input type="radio" id="attendance-yes" name="attendance" value="1">
    <label for="attendance-yes">Yes</label><br>

    <input type="radio" id="attendance-no" name="attendance" value="0">
    <label for="attendance-no">No</label><br>

    <input type="hidden" id="attended_status" name="attended_status" value="<?= esc_attr($args['attended_status']); ?>">
    <input type="hidden" id="not_attended_status" name="not_attended_status" value="<?= esc_attr($args['not_attended_status']); ?>">
    <button class="submit">Submit</button>
</form>