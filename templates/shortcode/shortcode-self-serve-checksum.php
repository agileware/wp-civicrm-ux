<?php
/**
 * Default template for the [ux_self_serve_checksum] shortcode.
 * 
 * To customise, copy this file into your theme and make changes as needed.
 */

// Disallow direct access
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !$args['form_submitted'] || 
        ( $args['form_submitted'] && isset( $args['turnstile_passed'] ) && !$args['turnstile_passed'] ) ) {
    ?>
    <?= wp_kses_post($args['form_text']); ?>
    <form id="ss-cs-form" method="post" data-turnstilepassed="<?= $args['turnstile_passed'] ? 'true' : 'false'; ?>">
        <p class="form-row">
            <label for="ss-cs-email">Your email:</label>
            <input type="email" id="ss-cs-email" name="ss-cs-email" required>
        </p>
        <input type="hidden" name="ss-cs-title" value="<?= esc_attr(get_the_title()); ?>">
        <input type="hidden" name="ss-cs-url" value="<?= esc_attr($args['url']); ?>">
        <?= wp_nonce_field( 'ux_self_serve_checksum', 'ux_self_serve_checksum_nonce', true, false ); ?>
        <?= wp_kses_post($args['turnstile']); ?>
        <div class="wp-block-button ss-cs-submit-wrapper">
            <button type="submit" name="ss-cs-submit" id="ss-cs-submit" class="wp-block-button__link wp-element-button button">Submit</button>
        </div>
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