<?php
/**
 * Default template for customising the [civicrm component="event" action="register"] shortcodes.
 * 
 * Alters default rendering of the shortcode when the CiviCRM Event is full and waitlist is not enabled.
 * Replicates the CiviCRM messages container.
 * 
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */

$message = $args['message'] ?? "";
?>


<div id="crm-container" class="crm-container">
    <div id="crm-main-content-wrapper">
        <div class="messages status no-popup alert" data-options="null">
            <i aria-hidden="true" class="crm-i fa-info-circle"></i><span class="msg-title"></span>
            <span class="msg-text"><?php echo $message; ?></span>
        </div>
    </div>
</div>