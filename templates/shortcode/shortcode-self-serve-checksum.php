<?php
/**
 * Default template for the [ux_self_serve_checksum] shortcode.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */


if ( !$args['form_submitted'] || 
        ( $args['form_submitted'] && isset( $args['turnstile_passed'] ) && !$args['turnstile_passed'] ) ) {
    
    echo $args['form_text']; ?>
    <form id="ss-cs-form" method="post" data-turnstilepassed="<?php echo $args['turnstile_passed'] ? 'true' : 'false'; ?>">
        <label for="ss-cs-email">Your email:</label>
        <input type="email" id="ss-cs-email" name="ss-cs-email" required>
        <input type="hidden" name="ss-cs-title" value="<?php echo get_the_title(); ?>">
        <input type="hidden" name="ss-cs-url" value="<?php echo $args['url']; ?>">
        <?php echo $args['turnstile']; ?>
        <button type="submit" name="ss-cs-submit" id="ss-cs-submit">Submit</button>
    </form>
    <?php
    if ( !empty($args['turnstile']) ) { ?>
    <script>
        let turnstileCompleted = false;
        
        // This function will be called when Turnstile completes successfully
        function onTurnstileComplete(token) {
            turnstileCompleted = true;

            let submitButton = document.getElementById('ss-cs-submit');
            submitButton.disabled = false;
        }
    </script>
    <?php } ?>
<?php } ?>